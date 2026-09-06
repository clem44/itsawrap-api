<?php

namespace App\Models;

use App\Models\Concerns\HasPrimaryImageMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Plank\Mediable\Mediable;

class Bundle extends Model
{
    use HasFactory, HasPrimaryImageMedia, Mediable, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'starts_at',
        'ends_at',
        'sort_order',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeCurrentlyActive(Builder $query, ?Carbon $now = null): Builder
    {
        $now ??= now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) use ($now) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $query) use ($now) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeAvailableForOrdering(Builder $query, ?Carbon $now = null): Builder
    {
        return $query
            ->currentlyActive($now)
            ->whereHas('bundleItems')
            ->whereDoesntHave('bundleItems.item', fn (Builder $query) => $query->where('active', false));
    }

    /**
     * @return array<int, string>
     */
    public static function apiRelations(): array
    {
        return [
            'bundleItems.item.category',
            'bundleItems.optionValues.itemOption.option',
            'bundleItems.optionValues.optionValue',
            'bundleItems.optionValues.parentOptionValue',
        ];
    }

    public function featuredImageUrl(): ?string
    {
        return $this->primary_image_url;
    }
}
