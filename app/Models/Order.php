<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'customer_id',
        'status_id',
        'subtotal',
        'discount',
        'discount_percent',
        'service_charge',
        'total',
        'comments',
        'placed_at',
        'is_delivery',
        'is_reward',
        'session_id',
        'source',
        'idempotency_key',
        'guest_access_token',
        'guest_access_token_hash',
        'guest_access_token_expires_at',
    ];

    protected $hidden = [
        'guest_access_token',
        'guest_access_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'service_charge' => 'decimal:2',
            'total' => 'decimal:2',
            'is_delivery' => 'boolean',
            'is_reward' => 'boolean',
            'placed_at' => 'datetime',
            'guest_access_token' => 'encrypted',
            'guest_access_token_expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashSession::class, 'session_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(OrderParticipant::class)->orderBy('sort_order');
    }

    public function rewardLedgerEntries(): HasMany
    {
        return $this->hasMany(RewardLedgerEntry::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tips(): HasMany
    {
        return $this->hasMany(Tip::class);
    }
}
