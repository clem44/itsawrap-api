<?php

namespace App\Models;

use App\Models\Concerns\HasPrimaryImageMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Mediable\Mediable;

class MealDeal extends Model
{
    use HasPrimaryImageMedia, Mediable, SoftDeletes;

    protected $fillable = ['name', 'description', 'base_price', 'service_charge_rate', 'marketing_max_saving', 'primary_category_id', 'primary_item_id', 'primary_group_key', 'selection_groups', 'fixed_option_value_ids', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'service_charge_rate' => 'decimal:4',
            'marketing_max_saving' => 'decimal:2',
            'selection_groups' => 'array',
            'fixed_option_value_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function primaryItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'primary_item_id');
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(OrderMealDeal::class);
    }
}
