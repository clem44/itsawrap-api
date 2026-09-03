<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Plank\Mediable\Mediable;

class Offer extends Model
{
    use HasFactory, Mediable, SoftDeletes;

    public const TYPE_PERCENTAGE_DISCOUNT = 'percentage_discount';

    public const TYPE_FIXED_DISCOUNT = 'fixed_discount';

    public const TYPE_BUY_X_GET_Y = 'buy_x_get_y';

    public const TYPE_SPEND_X_GET_Y = 'spend_x_get_y';

    public const TYPE_BUNDLE_FIXED_PRICE = 'bundle_fixed_price';

    public const DISCOUNT_PERCENT = 'percent';

    public const DISCOUNT_FIXED_AMOUNT = 'fixed_amount';

    public const DISCOUNT_FREE_ITEM = 'free_item';

    public const DISCOUNT_FIXED_PRICE = 'fixed_price';

    public const IMAGE_TAG = 'primary_image';

    protected $fillable = [
        'name',
        'description',
        'offer_type',
        'discount_type',
        'discount_value',
        'qualifying_category_id',
        'qualifying_item_id',
        'reward_category_id',
        'reward_item_id',
        'bundle_item_ids',
        'minimum_subtotal',
        'required_quantity',
        'reward_quantity',
        'starts_at',
        'ends_at',
        'is_active',
        'is_stackable',
        'priority',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_subtotal' => 'decimal:2',
            'bundle_item_ids' => 'array',
            'required_quantity' => 'integer',
            'reward_quantity' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'is_stackable' => 'boolean',
            'priority' => 'integer',
        ];
    }

    public static function offerTypes(): array
    {
        return [
            self::TYPE_PERCENTAGE_DISCOUNT,
            self::TYPE_FIXED_DISCOUNT,
            self::TYPE_BUY_X_GET_Y,
            self::TYPE_SPEND_X_GET_Y,
            self::TYPE_BUNDLE_FIXED_PRICE,
        ];
    }

    public static function discountTypes(): array
    {
        return [
            self::DISCOUNT_PERCENT,
            self::DISCOUNT_FIXED_AMOUNT,
            self::DISCOUNT_FREE_ITEM,
            self::DISCOUNT_FIXED_PRICE,
        ];
    }

    public function qualifyingCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'qualifying_category_id');
    }

    public function qualifyingItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'qualifying_item_id');
    }

    public function rewardCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'reward_category_id');
    }

    public function rewardItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'reward_item_id');
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

    public function featuredImageUrl(): ?string
    {
        return $this->firstMedia(self::IMAGE_TAG)?->getUrl();
    }
}
