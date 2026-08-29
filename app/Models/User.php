<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'firstname',
        'lastname',
        'username',
        'email',
        'password',
        'role_id',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_login' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function deliveryWindows(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryWindow::class, 'delivery_window_driver')
            ->withTimestamps();
    }

    public function assignedDeliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'assigned_driver_id');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function referralCode(): HasOne
    {
        return $this->hasOne(UserReferralCode::class);
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(UserReferral::class, 'referrer_user_id');
    }

    public function referredBy(): HasOne
    {
        return $this->hasOne(UserReferral::class, 'referred_user_id');
    }

    public function referralRewardAccounts(): HasMany
    {
        return $this->hasMany(UserReferralRewardAccount::class);
    }

    public function referralLedgerEntries(): HasMany
    {
        return $this->hasMany(UserReferralLedgerEntry::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }
}
