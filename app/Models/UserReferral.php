<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserReferral extends Model
{
    use HasFactory;

    public const STATUS_QUALIFIED = 'qualified';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'referral_program_id',
        'referrer_user_id',
        'referred_user_id',
        'status',
        'qualified_at',
        'reversed_at',
        'reversal_reason',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'qualified_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function referralProgram(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(UserReferralLedgerEntry::class);
    }
}
