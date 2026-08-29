<?php

namespace App\Services\Referrals;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReferralProgram;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserReferral;
use App\Models\UserReferralCode;
use App\Models\UserReferralLedgerEntry;
use App\Models\UserReferralRewardAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReferralService
{
    public function ensureCodeForUser(User $user): UserReferralCode
    {
        return DB::transaction(function () use ($user) {
            $existing = $user->referralCode()->first();

            if ($existing !== null) {
                return $existing;
            }

            return UserReferralCode::query()->create([
                'user_id' => $user->id,
                'code' => $this->generateUniqueCode(),
                'is_active' => true,
            ]);
        });
    }

    public function recordRegistrationReferral(User $referredUser, ?string $referralCode): ?UserReferral
    {
        $normalizedCode = $this->normalizeCode($referralCode);

        if ($normalizedCode === null) {
            return null;
        }

        return DB::transaction(function () use ($referredUser, $normalizedCode) {
            $program = $this->activeProgram();
            $code = UserReferralCode::query()
                ->where('code', $normalizedCode)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if ($program === null || $code === null) {
                throw ValidationException::withMessages([
                    'referral_code' => 'The selected referral code is invalid.',
                ]);
            }

            if ((int) $code->user_id === (int) $referredUser->id) {
                throw ValidationException::withMessages([
                    'referral_code' => 'You cannot use your own referral code.',
                ]);
            }

            $alreadyReferred = UserReferral::query()
                ->where('referred_user_id', $referredUser->id)
                ->where('referral_program_id', $program->id)
                ->exists();

            if ($alreadyReferred) {
                throw ValidationException::withMessages([
                    'referral_code' => 'This customer has already been referred.',
                ]);
            }

            $referral = UserReferral::query()->create([
                'referral_program_id' => $program->id,
                'referrer_user_id' => $code->user_id,
                'referred_user_id' => $referredUser->id,
                'status' => UserReferral::STATUS_QUALIFIED,
                'qualified_at' => now(),
            ]);

            UserReferralLedgerEntry::query()->create([
                'user_id' => $code->user_id,
                'referral_program_id' => $program->id,
                'user_referral_id' => $referral->id,
                'type' => UserReferralLedgerEntry::TYPE_QUALIFIED_REFERRAL,
                'progress_delta' => 1,
                'rewards_delta' => 0,
                'reason' => 'referred_user_registered',
                'metadata' => [
                    'referred_user_id' => $referredUser->id,
                    'referral_code' => $normalizedCode,
                ],
            ]);

            $this->rebuildAccount($code->user_id, $program);

            return $referral;
        });
    }

    public function redeemReferralReward(Order $order, OrderItem $orderItem, ReferralProgram $program, ?User $actor = null): UserReferralLedgerEntry
    {
        if (! $this->rewardsEnabled()) {
            throw ValidationException::withMessages([
                'referral_program_id' => 'Rewards are currently disabled.',
            ]);
        }

        $order->loadMissing('customer.user');
        $user = $order->customer?->user;

        if ($user === null) {
            throw ValidationException::withMessages([
                'customer_id' => 'A registered customer is required to redeem referral rewards.',
            ]);
        }

        $orderItem->loadMissing('item');

        if ($orderItem->item?->category_id !== $program->reward_category_id) {
            throw ValidationException::withMessages([
                'item_id' => 'This item is not eligible for the selected referral reward program.',
            ]);
        }

        return DB::transaction(function () use ($order, $orderItem, $program, $actor, $user) {
            $account = $this->rebuildAccount($user->id, $program);
            $redeemedQuantity = max(1, $orderItem->quantity);

            if ($account->rewards_available < $redeemedQuantity) {
                throw ValidationException::withMessages([
                    'referral_program_id' => 'This customer does not have an available referral reward.',
                ]);
            }

            $entry = UserReferralLedgerEntry::query()->create([
                'user_id' => $user->id,
                'referral_program_id' => $program->id,
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'type' => UserReferralLedgerEntry::TYPE_REDEEMED,
                'progress_delta' => 0,
                'rewards_delta' => -1 * $redeemedQuantity,
                'reason' => 'reward_redemption',
                'metadata' => [
                    'item_id' => $orderItem->item_id,
                    'quantity' => $redeemedQuantity,
                ],
                'created_by_user_id' => $actor?->id,
            ]);

            $orderItem->update([
                'is_reward_item' => true,
                'referral_program_id' => $program->id,
                'referral_ledger_entry_id' => $entry->id,
                'reward_discount_amount' => $orderItem->reward_discount_amount ?? ((float) $orderItem->price * $redeemedQuantity),
                'price' => 0,
            ]);

            $this->rebuildAccount($user->id, $program);

            return $entry;
        });
    }

    public function adjustUser(
        User $user,
        ReferralProgram $program,
        int $progressDelta,
        int $rewardsDelta,
        string $note,
        ?User $actor = null
    ): UserReferralLedgerEntry {
        return DB::transaction(function () use ($user, $program, $progressDelta, $rewardsDelta, $note, $actor) {
            $entry = UserReferralLedgerEntry::query()->create([
                'user_id' => $user->id,
                'referral_program_id' => $program->id,
                'type' => UserReferralLedgerEntry::TYPE_ADJUSTED,
                'progress_delta' => $progressDelta,
                'rewards_delta' => $rewardsDelta,
                'reason' => 'admin_adjustment',
                'metadata' => ['note' => $note],
                'created_by_user_id' => $actor?->id,
            ]);

            $this->rebuildAccount($user->id, $program);

            return $entry;
        });
    }

    public function reverseReferral(UserReferral $referral, string $reason, ?User $actor = null): void
    {
        if ($referral->status === UserReferral::STATUS_REVERSED) {
            return;
        }

        DB::transaction(function () use ($referral, $reason, $actor) {
            $referral->update([
                'status' => UserReferral::STATUS_REVERSED,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            $entries = UserReferralLedgerEntry::query()
                ->where('user_referral_id', $referral->id)
                ->whereDoesntHave('reversal')
                ->get();
            $reversalEntries = collect();

            foreach ($entries as $entry) {
                $reversalEntries->push(UserReferralLedgerEntry::query()->create([
                    'user_id' => $entry->user_id,
                    'referral_program_id' => $entry->referral_program_id,
                    'user_referral_id' => $referral->id,
                    'reverses_ledger_entry_id' => $entry->id,
                    'type' => UserReferralLedgerEntry::TYPE_REVERSED,
                    'progress_delta' => -1 * $entry->progress_delta,
                    'rewards_delta' => -1 * $entry->rewards_delta,
                    'reason' => 'referral_reversed',
                    'metadata' => [
                        'note' => $reason,
                        'reversed_type' => $entry->type,
                    ],
                    'created_by_user_id' => $actor?->id,
                ]));
            }

            $this->rebuildAccount($referral->referrer_user_id, $referral->referralProgram);
            $shortfall = $this->rewardShortfall($referral->referrer_user_id, $referral->referralProgram);

            if ($shortfall > 0) {
                $reversalEntries->each(function (UserReferralLedgerEntry $entry) use ($shortfall) {
                    $metadata = $entry->metadata ?? [];
                    $metadata['reward_clawback_shortfall'] = $shortfall;
                    $metadata['admin_notice'] = 'Referral was reversed, but already-redeemed rewards could not be fully clawed back.';

                    $entry->update(['metadata' => $metadata]);
                });
            }
        });
    }

    public function summaryForUser(User $user): ?array
    {
        $program = $this->activeProgram();

        if ($program === null) {
            return null;
        }

        $code = $this->ensureCodeForUser($user);
        $account = $this->rebuildAccount($user->id, $program);

        return [
            'program_id' => $program->id,
            'name' => $program->name,
            'code' => $code->code,
            'share_url' => rtrim((string) config('app.url'), '/').'/ref/'.$code->code,
            'reward_category' => $program->rewardCategory?->only(['id', 'name']),
            'required_referrals' => $program->required_referrals,
            'qualified_referrals' => $account->lifetime_qualified_referrals,
            'progress_quantity' => $account->progress_quantity,
            'rewards_available' => $account->rewards_available,
            'lifetime_qualified_referrals' => $account->lifetime_qualified_referrals,
            'lifetime_rewards_earned' => $account->lifetime_rewards_earned,
            'lifetime_rewards_redeemed' => $account->lifetime_rewards_redeemed,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ReferralProgram>
     */
    public function activePrograms(): \Illuminate\Database\Eloquent\Collection
    {
        return ReferralProgram::query()
            ->currentlyActive()
            ->with('rewardCategory')
            ->orderBy('name')
            ->get();
    }

    public function rebuildAccount(int $userId, ReferralProgram $program): UserReferralRewardAccount
    {
        $entries = UserReferralLedgerEntry::query()
            ->where('user_id', $userId)
            ->where('referral_program_id', $program->id)
            ->get();

        $netQualifiedReferrals = max(0, (int) $entries->sum('progress_delta'));
        $redeemedCount = abs((int) $entries
            ->where('type', UserReferralLedgerEntry::TYPE_REDEEMED)
            ->sum('rewards_delta'));
        $manualRewardDelta = (int) $entries
            ->whereIn('type', [
                UserReferralLedgerEntry::TYPE_ADJUSTED,
                UserReferralLedgerEntry::TYPE_EXPIRED,
                UserReferralLedgerEntry::TYPE_REVERSED,
            ])
            ->sum('rewards_delta');

        $threshold = max(1, $program->required_referrals);
        $earnedRewards = intdiv($netQualifiedReferrals, $threshold) * $program->reward_quantity;
        $availableRewards = max(0, $earnedRewards + $manualRewardDelta - $redeemedCount);

        return UserReferralRewardAccount::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'referral_program_id' => $program->id,
            ],
            [
                'progress_quantity' => $netQualifiedReferrals % $threshold,
                'rewards_available' => $availableRewards,
                'lifetime_qualified_referrals' => $netQualifiedReferrals,
                'lifetime_rewards_earned' => $earnedRewards,
                'lifetime_rewards_redeemed' => $redeemedCount,
            ]
        );
    }

    private function rewardShortfall(int $userId, ReferralProgram $program): int
    {
        $entries = UserReferralLedgerEntry::query()
            ->where('user_id', $userId)
            ->where('referral_program_id', $program->id)
            ->get();

        $netQualifiedReferrals = max(0, (int) $entries->sum('progress_delta'));
        $redeemedCount = abs((int) $entries
            ->where('type', UserReferralLedgerEntry::TYPE_REDEEMED)
            ->sum('rewards_delta'));
        $manualRewardDelta = (int) $entries
            ->whereIn('type', [
                UserReferralLedgerEntry::TYPE_ADJUSTED,
                UserReferralLedgerEntry::TYPE_EXPIRED,
                UserReferralLedgerEntry::TYPE_REVERSED,
            ])
            ->sum('rewards_delta');
        $earnedRewards = intdiv($netQualifiedReferrals, max(1, $program->required_referrals)) * $program->reward_quantity;

        return max(0, $redeemedCount - ($earnedRewards + $manualRewardDelta));
    }

    public function normalizeCode(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $normalized = strtoupper(trim($code));

        return $normalized === '' ? null : $normalized;
    }

    private function activeProgram(): ?ReferralProgram
    {
        return ReferralProgram::query()
            ->currentlyActive()
            ->with('rewardCategory')
            ->orderBy('name')
            ->first();
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (UserReferralCode::query()->where('code', $code)->exists());

        return $code;
    }

    private function rewardsEnabled(): bool
    {
        $value = Setting::getValue('rewards_enabled', 'true');

        return ! in_array(strtolower((string) $value), ['0', 'false', 'off', 'no'], true);
    }
}
