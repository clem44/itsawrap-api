<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserReferralLedgerEntry extends Model
{
    use HasFactory;

    public const TYPE_QUALIFIED_REFERRAL = 'qualified_referral';

    public const TYPE_REDEEMED = 'redeemed';

    public const TYPE_REVERSED = 'reversed';

    public const TYPE_ADJUSTED = 'adjusted';

    public const TYPE_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'referral_program_id',
        'user_referral_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referralProgram(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class);
    }

    public function userReferral(): BelongsTo
    {
        return $this->belongsTo(UserReferral::class);
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
