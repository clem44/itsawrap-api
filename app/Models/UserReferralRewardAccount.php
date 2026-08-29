<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReferralRewardAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'referral_program_id',
        'progress_quantity',
        'rewards_available',
        'lifetime_qualified_referrals',
        'lifetime_rewards_earned',
        'lifetime_rewards_redeemed',
    ];

    protected function casts(): array
    {
        return [
            'progress_quantity' => 'integer',
            'rewards_available' => 'integer',
            'lifetime_qualified_referrals' => 'integer',
            'lifetime_rewards_earned' => 'integer',
            'lifetime_rewards_redeemed' => 'integer',
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
}
