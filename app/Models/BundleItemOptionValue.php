<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleItemOptionValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'bundle_item_id',
        'item_option_id',
        'option_value_id',
        'quantity',
        'parent_option_value_id',
        'price_override',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price_override' => 'decimal:2',
        ];
    }

    public function bundleItem(): BelongsTo
    {
        return $this->belongsTo(BundleItem::class);
    }

    public function itemOption(): BelongsTo
    {
        return $this->belongsTo(ItemOption::class);
    }

    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(OptionValue::class);
    }

    public function parentOptionValue(): BelongsTo
    {
        return $this->belongsTo(OptionValue::class, 'parent_option_value_id');
    }
}
