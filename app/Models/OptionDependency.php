<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionDependency extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_option_value_id',
        'child_option_id',
    ];

    public function parentOptionValue(): BelongsTo
    {
        return $this->belongsTo(ItemOptionValue::class, 'parent_option_value_id');
    }

    public function childOption(): BelongsTo
    {
        return $this->belongsTo(ItemOption::class, 'child_option_id');
    }
}
