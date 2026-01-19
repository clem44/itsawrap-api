<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemOptionValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_option_id',
        'option_value_id',
        'price',
        'in_stock',
        'option_dependency_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'in_stock' => 'boolean',
        ];
    }

    public function itemOption(): BelongsTo
    {
        return $this->belongsTo(ItemOption::class);
    }

    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(OptionValue::class);
    }

    public function parentDependencies(): HasMany
    {
        return $this->hasMany(OptionDependency::class, 'parent_option_value_id');
    }
}
