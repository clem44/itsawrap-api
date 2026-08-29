<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'firstname',
        'lastname',
        'phone',
        'email',
        'user_id',
        'source',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function rewardAccounts(): HasMany
    {
        return $this->hasMany(CustomerRewardAccount::class);
    }

    public function rewardLedgerEntries(): HasMany
    {
        return $this->hasMany(RewardLedgerEntry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->name ?: trim(($this->firstname ?? '').' '.($this->lastname ?? ''));

        return $name ?: 'Customer';
    }
}
