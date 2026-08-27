<?php

namespace App\Services\Delivery;

use App\Models\Delivery;
use App\Models\DeliveryWindow;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class DeliveryScheduler
{
    public function today(): Collection
    {
        $today = $this->todayDate();

        return $this->windowsForDate($today)->map(fn (DeliveryWindow $window): array => $this->formatSlot($window, $today));
    }

    /**
     * @param  array<string, mixed>  $deliveryData
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function withReservationLock(array $deliveryData, callable $callback): mixed
    {
        if (! filter_var($deliveryData['is_delivery'] ?? false, FILTER_VALIDATE_BOOL)) {
            return $callback();
        }

        $deliveryWindowId = (int) ($deliveryData['delivery_window_id'] ?? 0);
        $today = $this->todayDate();
        $lock = Cache::lock("delivery-window:{$deliveryWindowId}:{$today->toDateString()}", 15);

        if (! $lock->get()) {
            abort(response()->json(['message' => 'This delivery window is already being reserved.'], 409));
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<string, mixed>  $deliveryData
     */
    public function reserveForOrder(Order $order, array $deliveryData): Delivery
    {
        $deliveryWindowId = (int) ($deliveryData['delivery_window_id'] ?? 0);

        $slot = $this->today()
            ->firstWhere('delivery_window_id', $deliveryWindowId);

        if ($slot === null) {
            throw ValidationException::withMessages([
                'delivery_window_id' => 'The selected delivery window is not available today.',
            ]);
        }

        if ($slot['is_available'] !== true) {
            throw ValidationException::withMessages([
                'delivery_window_id' => 'The selected delivery window is no longer available.',
            ]);
        }

        return Delivery::query()->create([
            'order_id' => $order->id,
            'delivery_window_id' => $deliveryWindowId,
            'delivery_date' => $slot['delivery_date'],
            'window_start_at' => $slot['starts_at'],
            'window_end_at' => $slot['ends_at'],
            'address' => $deliveryData['delivery_address'],
            'latitude' => $deliveryData['delivery_latitude'],
            'longitude' => $deliveryData['delivery_longitude'],
            'delivery_instructions' => $deliveryData['delivery_instructions'] ?? null,
            'status' => 'pending',
        ]);
    }

    private function windowsForDate(CarbonImmutable $date): Collection
    {
        $specificDateWindows = DeliveryWindow::query()
            ->withCount(['drivers', 'deliveries' => function ($query) use ($date) {
                $query->whereDate('delivery_date', $date->toDateString());
            }])
            ->where('schedule_type', DeliveryWindow::TYPE_SPECIFIC_DATE)
            ->whereDate('delivery_date', $date->toDateString())
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($specificDateWindows->isNotEmpty()) {
            return $specificDateWindows;
        }

        $weekdayWindows = $this->weekdayRecurringWindowsForDate($date);
        $everydayWindows = $this->everydayRecurringWindowsForDate($date);

        if ($weekdayWindows->isEmpty()) {
            return $everydayWindows;
        }

        return $weekdayWindows
            ->concat($everydayWindows->reject(fn (DeliveryWindow $everydayWindow): bool => $this->overlapsAny($everydayWindow, $weekdayWindows)))
            ->sortBy('start_time')
            ->values();
    }

    private function weekdayRecurringWindowsForDate(CarbonImmutable $date): Collection
    {
        return $this->recurringWindowsForDate($date)
            ->where('day_of_week', (int) $date->dayOfWeekIso)
            ->get();
    }

    private function everydayRecurringWindowsForDate(CarbonImmutable $date): Collection
    {
        return $this->recurringWindowsForDate($date)
            ->whereNull('day_of_week')
            ->get();
    }

    private function recurringWindowsForDate(CarbonImmutable $date): Builder
    {
        return DeliveryWindow::query()
            ->withCount(['drivers', 'deliveries' => function ($query) use ($date) {
                $query->whereDate('delivery_date', $date->toDateString());
            }])
            ->where('schedule_type', DeliveryWindow::TYPE_WEEKLY)
            ->where('is_active', true)
            ->orderBy('start_time');
    }

    private function overlapsAny(DeliveryWindow $window, Collection $windows): bool
    {
        return $windows->contains(fn (DeliveryWindow $otherWindow): bool => $this->windowsOverlap($window, $otherWindow));
    }

    private function windowsOverlap(DeliveryWindow $window, DeliveryWindow $otherWindow): bool
    {
        return (string) $window->start_time < (string) $otherWindow->end_time
            && (string) $window->end_time > (string) $otherWindow->start_time;
    }

    private function formatSlot(DeliveryWindow $window, CarbonImmutable $date): array
    {
        $startsAt = $this->dateTimeForWindowTime($date, (string) $window->start_time);
        $endsAt = $this->dateTimeForWindowTime($date, (string) $window->end_time);
        $remainingCapacity = max(0, $window->capacity - (int) $window->deliveries_count);
        $unavailableReason = null;

        if ($this->now()->greaterThanOrEqualTo($startsAt)) {
            $unavailableReason = 'time_passed';
        } elseif ($remainingCapacity < 1) {
            $unavailableReason = 'full';
        }

        return [
            'delivery_window_id' => $window->id,
            'label' => $startsAt->format('g:i A').' - '.$endsAt->format('g:i A'),
            'delivery_date' => $date->toDateString(),
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $endsAt->toIso8601String(),
            'capacity' => $window->capacity,
            'remaining_capacity' => $remainingCapacity,
            'driver_count' => (int) $window->drivers_count,
            'is_available' => $unavailableReason === null,
            'unavailable_reason' => $unavailableReason,
        ];
    }

    private function dateTimeForWindowTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($date->toDateString().' '.$time, $this->timezone());
    }

    private function todayDate(): CarbonImmutable
    {
        return $this->now()->startOfDay();
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    private function timezone(): string
    {
        return config('app.timezone', 'UTC');
    }
}
