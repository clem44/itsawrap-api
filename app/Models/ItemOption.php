<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'option_id',
        'required',
        'type',
        'range',
        'max',
        'min',
        'qty',
        'enable_qty',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'range' => 'integer',
            'max' => 'integer',
            'min' => 'integer',
            'qty' => 'integer',
            'enable_qty' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function itemOptionValues(): HasMany
    {
        return $this->hasMany(ItemOptionValue::class);
    }

    public function childDependencies(): HasMany
    {
        return $this->hasMany(OptionDependency::class, 'child_option_id');
    }
}
