<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRewardAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'reward_program_id',
        'progress_quantity',
        'rewards_available',
        'lifetime_qualifying_quantity',
        'lifetime_rewards_earned',
        'lifetime_rewards_redeemed',
    ];

    protected function casts(): array
    {
        return [
            'progress_quantity' => 'integer',
            'rewards_available' => 'integer',
            'lifetime_qualifying_quantity' => 'integer',
            'lifetime_rewards_earned' => 'integer',
            'lifetime_rewards_redeemed' => 'integer',
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
}
