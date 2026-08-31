<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryWindow;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'delivery_window_id' => ['nullable', 'integer', 'exists:delivery_windows,id'],
        ]);

        $deliveries = Delivery::query()
            ->with(['order.customer', 'deliveryWindow', 'assignedDriver'])
            ->when($validated['delivery_window_id'] ?? null, function ($query, int $deliveryWindowId): void {
                $query->where('delivery_window_id', $deliveryWindowId);
            })
            ->orderByDesc('delivery_date')
            ->orderByDesc('window_start_at')
            ->paginate(15)
            ->withQueryString();

        $deliveryWindows = DeliveryWindow::query()
            ->orderByRaw("CASE WHEN schedule_type = 'specific_date' THEN 0 ELSE 1 END")
            ->orderBy('delivery_date')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('admin.deliveries.index', compact('deliveries', 'deliveryWindows'));
    }

    public function edit(Delivery $delivery): View
    {
        $delivery->load(['order.customer', 'deliveryWindow', 'assignedDriver']);

        $deliveryWindows = DeliveryWindow::query()
            ->orderByRaw("CASE WHEN schedule_type = 'specific_date' THEN 0 ELSE 1 END")
            ->orderBy('delivery_date')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $drivers = User::query()
            ->where('role_id', Role::DRIVER_ID)
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get();

        return view('admin.deliveries.edit', compact('delivery', 'deliveryWindows', 'drivers'));
    }

    public function update(Request $request, Delivery $delivery): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_window_id' => ['required', 'integer', 'exists:delivery_windows,id'],
            'delivery_date' => ['required', 'date'],
            'assigned_driver_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role_id', Role::DRIVER_ID)),
            ],
            'address' => ['required', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['pending', 'assigned', 'out_for_delivery', 'delivered', 'cancelled'])],
        ]);

        $deliveryWindow = DeliveryWindow::query()->findOrFail($validated['delivery_window_id']);
        $deliveryDate = $this->validatedDeliveryDate($deliveryWindow, $validated['delivery_date']);

        DB::transaction(function () use ($delivery, $deliveryWindow, $deliveryDate, $validated): void {
            $delivery->update([
                'delivery_window_id' => $deliveryWindow->id,
                'assigned_driver_id' => $validated['assigned_driver_id'] ?? null,
                'delivery_date' => $deliveryDate->toDateString(),
                'window_start_at' => $this->dateTimeForWindowTime($deliveryDate, (string) $deliveryWindow->start_time),
                'window_end_at' => $this->dateTimeForWindowTime($deliveryDate, (string) $deliveryWindow->end_time),
                'address' => $validated['address'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'delivery_instructions' => $validated['delivery_instructions'] ?? null,
                'status' => $validated['status'],
            ]);

            $delivery->order?->update(['is_delivery' => true]);
        });

        return redirect()->route('admin.deliveries.edit', $delivery)
            ->with('success', 'Delivery updated successfully.');
    }

    public function destroy(Delivery $delivery): RedirectResponse
    {
        DB::transaction(function () use ($delivery): void {
            $delivery->order?->update(['is_delivery' => false]);
            $delivery->delete();
        });

        return redirect()->route('admin.deliveries.index')
            ->with('success', 'Delivery trashed successfully.');
    }

    private function validatedDeliveryDate(DeliveryWindow $deliveryWindow, string $date): CarbonImmutable
    {
        if ($deliveryWindow->schedule_type === DeliveryWindow::TYPE_SPECIFIC_DATE) {
            return CarbonImmutable::parse($deliveryWindow->delivery_date, $this->timezone())->startOfDay();
        }

        $deliveryDate = CarbonImmutable::parse($date, $this->timezone())->startOfDay();

        if ($deliveryWindow->day_of_week !== null && (int) $deliveryWindow->day_of_week !== (int) $deliveryDate->dayOfWeekIso) {
            throw ValidationException::withMessages([
                'delivery_date' => 'The delivery date must match the selected weekly delivery window day.',
            ]);
        }

        return $deliveryDate;
    }

    private function dateTimeForWindowTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($date->toDateString().' '.$time, $this->timezone());
    }

    private function timezone(): string
    {
        return config('delivery.timezone', 'America/Anguilla');
    }
}
