<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Plank\Mediable\Mediable;

class Slide extends Model
{
    use HasFactory, Mediable;

    public const IMAGE_TAG = 'hero_image';

    protected $fillable = [
        'sort_order',
        'is_active',
        'badge',
        'heading',
        'body',
        'text_alignment',
        'overlay_color',
        'overlay_opacity',
        'transition',
        'transition_ms',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'overlay_opacity' => 'integer',
            'transition_ms' => 'integer',
        ];
    }

    public function slider(): BelongsTo
    {
        return $this->belongsTo(Slider::class);
    }

    public function buttons(): HasMany
    {
        return $this->hasMany(SlideButton::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
