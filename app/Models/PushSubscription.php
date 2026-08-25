<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

class PushSubscription extends Model
{
    use HasFactory;

    public const PROVIDER_FIREBASE = 'firebase';

    public const APP_CONTEXT_POS = 'pos';

    public const APP_CONTEXT_ADMIN = 'admin';

    public const APP_CONTEXT_CUSTOMER = 'customer';

    public const PLATFORM_IOS = 'ios';

    public const PLATFORM_ANDROID = 'android';

    public const PLATFORM_WEB = 'web';

    protected $fillable = [
        'user_id',
        'provider',
        'token',
        'token_hash',
        'platform',
        'app_context',
        'device_name',
        'personal_access_token_id',
        'last_seen_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token',
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personalAccessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function scopeForPosOrderConfirmation(Builder $query): Builder
    {
        return $query
            ->active()
            ->where('provider', self::PROVIDER_FIREBASE)
            ->where('app_context', self::APP_CONTEXT_POS)
            ->whereIn('platform', [self::PLATFORM_IOS, self::PLATFORM_ANDROID])
            ->whereHas('user', function (Builder $query): void {
                $query->whereIn('role_id', [1, 2]);
            });
    }

    public function markRevoked(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }
}
