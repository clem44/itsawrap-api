<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'required_referrals',
        'reward_category_id',
        'reward_quantity',
        'starts_at',
        'ends_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'required_referrals' => 'integer',
            'reward_quantity' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function rewardCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'reward_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(UserReferral::class);
    }

    public function rewardAccounts(): HasMany
    {
        return $this->hasMany(UserReferralRewardAccount::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(UserReferralLedgerEntry::class);
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }
}
