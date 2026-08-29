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
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'is_reward_item' => 'boolean',
            'reward_discount_amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
