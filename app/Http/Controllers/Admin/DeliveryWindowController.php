<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DeliveryWindow;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryWindowController extends Controller
{
    public function index(): View
    {
        $today = now(config('app.timezone'))->toDateString();
        $deliveryWindows = DeliveryWindow::query()
            ->with(['branch', 'drivers'])
            ->withCount(['deliveries as today_deliveries_count' => function ($query) use ($today) {
                $query->whereDate('delivery_date', $today);
            }])
            ->orderByRaw("CASE WHEN schedule_type = 'specific_date' THEN 0 ELSE 1 END")
            ->orderBy('delivery_date')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $branches = Branch::query()->where('active', true)->orderBy('name')->get();
        $drivers = User::query()
            ->where('role_id', Role::DRIVER_ID)
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get();

        return view('admin.delivery-windows.index', compact('deliveryWindows', 'branches', 'drivers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedWindow($request);

        DB::transaction(function () use ($request, $validated): void {
            $deliveryWindow = DeliveryWindow::query()->create($this->windowPayload($request, $validated));
            $deliveryWindow->drivers()->sync($validated['driver_ids'] ?? []);
        });

        return redirect()->route('admin.delivery-windows.index')
            ->with('success', 'Delivery window created successfully.');
    }

    public function update(Request $request, DeliveryWindow $deliveryWindow): RedirectResponse
    {
        $validated = $this->validatedWindow($request);

        DB::transaction(function () use ($request, $deliveryWindow, $validated): void {
            $deliveryWindow->update($this->windowPayload($request, $validated));
            $deliveryWindow->drivers()->sync($validated['driver_ids'] ?? []);
        });

        return redirect()->route('admin.delivery-windows.index')
            ->with('success', 'Delivery window updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedWindow(Request $request): array
    {
        $validated = $request->validate([
            'schedule_type' => ['required', Rule::in([DeliveryWindow::TYPE_WEEKLY, DeliveryWindow::TYPE_SPECIFIC_DATE])],
            'day_of_week' => ['nullable', 'integer', 'between:1,7', 'required_if:schedule_type,'.DeliveryWindow::TYPE_WEEKLY],
            'delivery_date' => ['nullable', 'date', 'required_if:schedule_type,'.DeliveryWindow::TYPE_SPECIFIC_DATE],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1', 'max:999'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'driver_ids' => ['nullable', 'array'],
            'driver_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role_id', Role::DRIVER_ID)),
            ],
        ]);

        $validated['driver_ids'] = array_values(array_unique($validated['driver_ids'] ?? []));

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function windowPayload(Request $request, array $validated): array
    {
        return [
            'schedule_type' => $validated['schedule_type'],
            'day_of_week' => $validated['schedule_type'] === DeliveryWindow::TYPE_WEEKLY ? $validated['day_of_week'] : null,
            'delivery_date' => $validated['schedule_type'] === DeliveryWindow::TYPE_SPECIFIC_DATE ? $validated['delivery_date'] : null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'capacity' => $validated['capacity'],
            'is_active' => $request->has('is_active'),
            'branch_id' => $validated['branch_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }
}
