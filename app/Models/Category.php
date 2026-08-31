<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Plank\Mediable\Mediable;


class Category extends Model
{
    use HasFactory, Mediable;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'sort_order',
        'media_id',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function earningRewardPrograms(): HasMany
    {
        return $this->hasMany(RewardProgram::class, 'earn_category_id');
    }

    public function redeemableRewardPrograms(): HasMany
    {
        return $this->hasMany(RewardProgram::class, 'reward_category_id');
    }

    public function redeemableReferralPrograms(): HasMany
    {
        return $this->hasMany(ReferralProgram::class, 'reward_category_id');
    }
}
