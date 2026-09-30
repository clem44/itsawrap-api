<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Slider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'autoplay',
        'autoplay_interval_ms',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'autoplay' => 'boolean',
            'autoplay_interval_ms' => 'integer',
        ];
    }

    public function slides(): HasMany
    {
        return $this->hasMany(Slide::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
