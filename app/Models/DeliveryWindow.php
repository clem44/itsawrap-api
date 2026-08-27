<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryWindow extends Model
{
    public const TYPE_WEEKLY = 'weekly';

    public const TYPE_SPECIFIC_DATE = 'specific_date';

    public const WEEKDAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    protected $fillable = [
        'schedule_type',
        'day_of_week',
        'delivery_date',
        'start_time',
        'end_time',
        'capacity',
        'is_active',
        'branch_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'delivery_window_driver')
            ->withTimestamps();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function recurringDayLabel(): string
    {
        if ($this->day_of_week === null) {
            return 'Everyday';
        }

        return self::WEEKDAYS[$this->day_of_week] ?? 'Unknown';
    }
}
