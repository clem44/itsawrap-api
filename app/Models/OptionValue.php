<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OptionValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'option_id',
        'name',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function itemOptionValues(): HasMany
    {
        return $this->hasMany(ItemOptionValue::class);
    }

    public function orderItemOptions(): HasMany
    {
        return $this->hasMany(OrderItemOption::class);
    }
}
