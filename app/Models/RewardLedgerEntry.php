<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RewardLedgerEntry extends Model
{
    use HasFactory;

    public const TYPE_EARNED_PROGRESS = 'earned_progress';

    public const TYPE_REDEEMED = 'redeemed';

    public const TYPE_REVERSED = 'reversed';

    public const TYPE_ADJUSTED = 'adjusted';

    public const TYPE_EXPIRED = 'expired';

    protected $fillable = [
        'customer_id',
        'reward_program_id',
        'order_id',
        'order_item_id',
        'reverses_ledger_entry_id',
        'type',
        'progress_delta',
        'rewards_delta',
        'reason',
        'metadata',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'progress_delta' => 'integer',
            'rewards_delta' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function rewardProgram(): BelongsTo
    {
        return $this->belongsTo(RewardProgram::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_ledger_entry_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reverses_ledger_entry_id');
    }
}
