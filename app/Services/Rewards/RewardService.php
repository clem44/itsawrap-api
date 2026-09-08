<?php

namespace App\Services\Rewards;

use App\Models\Customer;
use App\Models\CustomerRewardAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RewardLedgerEntry;
use App\Models\RewardProgram;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardService
{
    private const REWARD_ELIGIBLE_ORDER_STATUSES = [
        'active',
        'completed',
    ];

    public function recordOrderCompleted(Order $order): void
    {
        $this->recordEligibleOrderRewards($order);
    }

    public function recordEligibleOrderRewards(Order $order): void
    {
        if (! $this->rewardsEnabled() || $order->customer_id === null) {
            return;
        }

        $order->loadMissing(['status', 'orderItems.item']);

        if (! in_array($order->status?->name, self::REWARD_ELIGIBLE_ORDER_STATUSES, true)) {
            return;
        }

        DB::transaction(function () use ($order) {
            RewardProgram::query()
                ->currentlyActive()
                ->get()
                ->each(function (RewardProgram $program) use ($order) {
                    $alreadyRecorded = RewardLedgerEntry::query()
                        ->where('order_id', $order->id)
                        ->where('reward_program_id', $program->id)
                        ->where('type', RewardLedgerEntry::TYPE_EARNED_PROGRESS)
                        ->exists();

                    if ($alreadyRecorded) {
                        return;
                    }

                    $qualifyingQuantity = $order->orderItems
                        ->filter(fn (OrderItem $orderItem) => ! $orderItem->is_reward_item)
                        ->filter(fn (OrderItem $orderItem) => $orderItem->item?->category_id === $program->earn_category_id)
                        ->sum('quantity');

                    if ($qualifyingQuantity <= 0) {
                        return;
                    }

                    RewardLedgerEntry::query()->create([
                        'customer_id' => $order->customer_id,
                        'reward_program_id' => $program->id,
                        'order_id' => $order->id,
                        'type' => RewardLedgerEntry::TYPE_EARNED_PROGRESS,
                        'progress_delta' => $qualifyingQuantity,
                        'rewards_delta' => 0,
                        'reason' => 'order_completed',
                        'metadata' => [
                            'order_number' => $order->number,
                            'qualifying_item_quantity_required' => $program->qualifying_item_quantity_required,
                        ],
                    ]);

                    $this->rebuildAccount($order->customer_id, $program);
                });
        });
    }

    public function reverseOrder(Order $order, string $reason, ?User $actor = null): void
    {
        if ($order->customer_id === null) {
            return;
        }

        DB::transaction(function () use ($order, $reason, $actor) {
            $entries = RewardLedgerEntry::query()
                ->where('order_id', $order->id)
                ->whereIn('type', [
                    RewardLedgerEntry::TYPE_EARNED_PROGRESS,
                    RewardLedgerEntry::TYPE_REDEEMED,
                    RewardLedgerEntry::TYPE_ADJUSTED,
                    RewardLedgerEntry::TYPE_EXPIRED,
                ])
                ->whereDoesntHave('reversal')
                ->get();

            $programIds = [];

            foreach ($entries as $entry) {
                RewardLedgerEntry::query()->create([
                    'customer_id' => $entry->customer_id,
                    'reward_program_id' => $entry->reward_program_id,
                    'order_id' => $order->id,
                    'order_item_id' => $entry->order_item_id,
                    'reverses_ledger_entry_id' => $entry->id,
                    'type' => RewardLedgerEntry::TYPE_REVERSED,
                    'progress_delta' => -1 * $entry->progress_delta,
                    'rewards_delta' => -1 * $entry->rewards_delta,
                    'reason' => $reason,
                    'metadata' => [
                        'reversed_type' => $entry->type,
                        'reversed_reason' => $entry->reason,
                    ],
                    'created_by_user_id' => $actor?->id,
                ]);

                $programIds[] = $entry->reward_program_id;
            }

            RewardProgram::query()
                ->whereIn('id', array_unique($programIds))
                ->get()
                ->each(fn (RewardProgram $program) => $this->rebuildAccount($order->customer_id, $program));
        });
    }

    public function redeemReward(Order $order, OrderItem $orderItem, RewardProgram $program, ?User $actor = null): RewardLedgerEntry
    {
        if (! $this->rewardsEnabled()) {
            throw ValidationException::withMessages([
                'reward_program_id' => 'Rewards are currently disabled.',
            ]);
        }

        if ($order->customer_id === null) {
            throw ValidationException::withMessages([
                'customer_id' => 'A customer is required to redeem rewards.',
            ]);
        }

        $orderItem->loadMissing('item');

        if ($orderItem->item?->category_id !== $program->reward_category_id) {
            throw ValidationException::withMessages([
                'item_id' => 'This item is not eligible for the selected reward program.',
            ]);
        }

        return DB::transaction(function () use ($order, $orderItem, $program, $actor) {
            $account = $this->rebuildAccount($order->customer_id, $program);
            $redeemedQuantity = max(1, $orderItem->quantity);

            if ($account->rewards_available < $redeemedQuantity) {
                throw ValidationException::withMessages([
                    'reward_program_id' => 'This customer does not have an available reward.',
                ]);
            }

            $entry = RewardLedgerEntry::query()->create([
                'customer_id' => $order->customer_id,
                'reward_program_id' => $program->id,
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'type' => RewardLedgerEntry::TYPE_REDEEMED,
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
                'reward_program_id' => $program->id,
                'reward_ledger_entry_id' => $entry->id,
                // The wrap's cost lives in its options, so the value given
                // away is the whole line, not the 0.00 base price.
                'reward_discount_amount' => $orderItem->reward_discount_amount ?? $orderItem->undiscountedLineTotal(),
                'price' => 0,
            ]);

            $this->rebuildAccount($order->customer_id, $program);

            return $entry;
        });
    }

    public function summaryForCustomer(Customer $customer): array
    {
        $programs = RewardProgram::query()
            ->currentlyActive()
            ->with(['earnCategory', 'rewardCategory'])
            ->orderBy('name')
            ->get();

        return [
            'rewards_enabled' => $this->rewardsEnabled(),
            'programs' => $programs->map(function (RewardProgram $program) use ($customer) {
                $account = $this->rebuildAccount($customer->id, $program);

                return [
                    'program_id' => $program->id,
                    'name' => $program->name,
                    'earn_category' => $program->earnCategory?->only(['id', 'name']),
                    'reward_category' => $program->rewardCategory?->only(['id', 'name']),
                    'qualifying_item_quantity_required' => $program->qualifying_item_quantity_required,
                    'reward_quantity' => $program->reward_quantity,
                    'progress_quantity' => $account->progress_quantity,
                    'rewards_available' => $account->rewards_available,
                    'lifetime_qualifying_quantity' => $account->lifetime_qualifying_quantity,
                    'lifetime_rewards_earned' => $account->lifetime_rewards_earned,
                    'lifetime_rewards_redeemed' => $account->lifetime_rewards_redeemed,
                ];
            })->values(),
        ];
    }

    public function adjustCustomer(
        Customer $customer,
        RewardProgram $program,
        int $progressDelta,
        int $rewardsDelta,
        string $note,
        ?User $actor = null
    ): RewardLedgerEntry {
        return DB::transaction(function () use ($customer, $program, $progressDelta, $rewardsDelta, $note, $actor) {
            $entry = RewardLedgerEntry::query()->create([
                'customer_id' => $customer->id,
                'reward_program_id' => $program->id,
                'type' => RewardLedgerEntry::TYPE_ADJUSTED,
                'progress_delta' => $progressDelta,
                'rewards_delta' => $rewardsDelta,
                'reason' => 'admin_adjustment',
                'metadata' => ['note' => $note],
                'created_by_user_id' => $actor?->id,
            ]);

            $this->rebuildAccount($customer->id, $program);

            return $entry;
        });
    }

    public function rebuildAccount(int $customerId, RewardProgram $program): CustomerRewardAccount
    {
        $entries = RewardLedgerEntry::query()
            ->where('customer_id', $customerId)
            ->where('reward_program_id', $program->id)
            ->get();

        $netQualifyingQuantity = max(0, (int) $entries->sum('progress_delta'));
        $redeemedCount = abs((int) $entries
            ->where('type', RewardLedgerEntry::TYPE_REDEEMED)
            ->sum('rewards_delta'));
        $manualRewardDelta = (int) $entries
            ->whereIn('type', [
                RewardLedgerEntry::TYPE_ADJUSTED,
                RewardLedgerEntry::TYPE_EXPIRED,
                RewardLedgerEntry::TYPE_REVERSED,
            ])
            ->sum('rewards_delta');

        $threshold = max(1, $program->qualifying_item_quantity_required);
        $earnedRewards = intdiv($netQualifyingQuantity, $threshold) * $program->reward_quantity;
        $availableRewards = max(0, $earnedRewards + $manualRewardDelta - $redeemedCount);

        return CustomerRewardAccount::query()->updateOrCreate(
            [
                'customer_id' => $customerId,
                'reward_program_id' => $program->id,
            ],
            [
                'progress_quantity' => $netQualifyingQuantity % $threshold,
                'rewards_available' => $availableRewards,
                'lifetime_qualifying_quantity' => $netQualifyingQuantity,
                'lifetime_rewards_earned' => $earnedRewards,
                'lifetime_rewards_redeemed' => $redeemedCount,
            ]
        );
    }

    /**
     * @return Collection<int, RewardProgram>
     */
    public function activePrograms(): Collection
    {
        return RewardProgram::query()
            ->currentlyActive()
            ->with(['earnCategory', 'rewardCategory'])
            ->orderBy('name')
            ->get();
    }

    private function rewardsEnabled(): bool
    {
        $value = Setting::getValue('rewards_enabled', 'true');

        return ! in_array(strtolower((string) $value), ['0', 'false', 'off', 'no'], true);
    }
}
