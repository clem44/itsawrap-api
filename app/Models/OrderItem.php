<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_participant_id',
        'item_id',
        'price',
        'quantity',
        'is_reward_item',
        'reward_program_id',
        'reward_ledger_entry_id',
        'reward_discount_amount',
        'referral_program_id',
        'referral_ledger_entry_id',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'order_participant_id' => 'integer',
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'is_reward_item' => 'boolean',
            'reward_discount_amount' => 'decimal:2',
        ];
    }

    /**
     * What this line actually costs the customer: its base price plus the
     * options attached to it, times the number ordered. An item built entirely
     * from options — a wrap whose protein, sauce and side are all selections —
     * carries a 0.00 base price, so leaving the options out reports it as free.
     *
     * A redeemed reward line costs nothing: the discount it gave away is kept
     * on reward_discount_amount rather than being inferred from the price.
     */
    public function lineTotal(): float
    {
        if ($this->is_reward_item) {
            return 0.0;
        }

        return $this->undiscountedLineTotal();
    }

    /**
     * What the line would have cost had it been paid for — the value a reward
     * redemption gives away.
     */
    public function undiscountedLineTotal(): float
    {
        $optionsTotal = $this->orderItemOptions
            ->sum(fn (OrderItemOption $option): float => (float) $option->price * max(1, (int) ($option->qty ?? 1)));

        return round(((float) $this->price + $optionsTotal) * max(1, (int) $this->quantity), 2);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(OrderParticipant::class, 'order_participant_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function orderItemOptions(): HasMany
    {
        return $this->hasMany(OrderItemOption::class);
    }

    public function rewardProgram(): BelongsTo
    {
        return $this->belongsTo(RewardProgram::class);
    }

    public function rewardLedgerEntry(): BelongsTo
    {
        return $this->belongsTo(RewardLedgerEntry::class);
    }

    public function referralProgram(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class);
    }

    public function referralLedgerEntry(): BelongsTo
    {
        return $this->belongsTo(UserReferralLedgerEntry::class);
    }
}
