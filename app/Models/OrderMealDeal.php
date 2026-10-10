<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderMealDeal extends Model
{
    protected $fillable = ['meal_deal_id', 'order_participant_id', 'name', 'base_price', 'extras_price', 'service_charge', 'quantity', 'selections'];

    protected function casts(): array
    {
        return ['selections' => 'array', 'base_price' => 'decimal:2', 'extras_price' => 'decimal:2', 'service_charge' => 'decimal:2'];
    }

    public function mealDeal(): BelongsTo
    {
        return $this->belongsTo(MealDeal::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
