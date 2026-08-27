<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'order_id',
        'delivery_window_id',
        'assigned_driver_id',
        'delivery_date',
        'window_start_at',
        'window_end_at',
        'address',
        'latitude',
        'longitude',
        'delivery_instructions',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'window_start_at' => 'datetime',
            'window_end_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryWindow(): BelongsTo
    {
        return $this->belongsTo(DeliveryWindow::class);
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }
}
