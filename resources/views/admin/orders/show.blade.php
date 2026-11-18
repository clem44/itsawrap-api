@extends('admin.layouts.app')

@section('title', "Order #" . $order->number)
@section('header', "Order #" . $order->number)

@push('scripts')
<script>
    window.AdminVuePage = () => ({
        paymentModalOpen: @js($errors->payment->any()),
        paymentModalTrigger: null,
        paymentSubmitting: false,
        payment: {
            amount: @js(old('amount', number_format((float) $order->total, 2, '.', ''))),
            method: @js(old('method', 'cash')),
            cardLastFour: @js(old('card_last_four', '')),
        },

        initPage() {
            if (this.paymentModalOpen) this.focusPaymentForm();
        },

        openPaymentModal(event) {
            this.paymentModalTrigger = event?.currentTarget || null;
            this.payment = {
                amount: @js(number_format((float) $order->total, 2, '.', '')),
                method: 'cash',
                cardLastFour: '',
            };
            this.paymentSubmitting = false;
            this.paymentModalOpen = true;
            this.focusPaymentForm();
        },

        closePaymentModal() {
            if (this.paymentSubmitting) return;
            this.paymentModalOpen = false;
            this.$nextTick(() => this.paymentModalTrigger?.focus());
        },

        focusPaymentForm() {
            this.$nextTick(() => document.getElementById('payment-amount')?.focus());
        },

        trapPaymentFocus(event) {
            const dialog = document.getElementById('payment-dialog');
            const controls = [...dialog.querySelectorAll('button, input')]
                .filter(element => !element.disabled && element.getClientRects().length);
            if (!controls.length) return;
            const first = controls[0];
            const last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    });
</script>
@endpush

@section('content')
<div class="animate-in">
    <!-- Header with back button -->
    <div class="page-header animate-in mb-6">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.orders.index') }}" class="text-white/60 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Order #{{ $order->number }}</h1>
                    <p style="color: var(--color-sage-light); opacity: 0.9;">{{ $order->created_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
            @php
                $statusColor = match($order->status?->name ?? 'pending') {
                    'pending' => ['bg' => 'rgba(255, 193, 7, 0.2)', 'text' => '#FFC107'],
                    'confirmed' => ['bg' => 'rgba(33, 150, 243, 0.2)', 'text' => '#2196F3'],
                    'preparing' => ['bg' => 'rgba(156, 39, 176, 0.2)', 'text' => '#9C27B0'],
                    'ready' => ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50'],
                    'completed' => ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50'],
                    'cancelled' => ['bg' => 'rgba(244, 67, 54, 0.2)', 'text' => '#F44336'],
                    default => ['bg' => 'rgba(124, 154, 138, 0.2)', 'text' => 'var(--color-sage)']
                };
            @endphp
            <div class="inline-block px-4 py-2 text-sm font-semibold rounded-lg" style="background: {{ $statusColor['bg'] }}; color: {{ $statusColor['text'] }};">
                {{ $order->status?->name ?? 'Unknown' }}
            </div>
        </div>
    </div>

    <!-- Main content grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in animate-delay-1">
        <!-- Left column: Order details and items -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Customer Information -->
            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h2>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                            {{ strtoupper(substr($order->customer?->name ?? 'W', 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-gray-900 font-semibold">{{ $order->customer?->name ?: 'Walk-in Customer' }}</p>
                            @if($order->customer?->phone)
                                <p class="text-sm text-gray-600">{{ $order->customer->phone }}</p>
                            @endif
                            @if($order->customer?->email)
                                <p class="text-sm text-gray-600">{{ $order->customer->email }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($order->delivery)
                <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Delivery</h2>
                    <div class="space-y-3 text-sm text-gray-700">
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-600">Window</span>
                            <span class="font-semibold text-gray-900 text-right">
                                {{ $order->delivery->window_start_at->format('M d, Y g:i A') }} - {{ $order->delivery->window_end_at->format('g:i A') }}
                            </span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-600">Driver</span>
                            <span class="font-semibold text-gray-900 text-right">{{ $order->delivery->assignedDriver?->full_name ?? 'Unassigned' }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-600 mb-1">Address</span>
                            <p class="font-semibold text-gray-900">{{ $order->delivery->address }}</p>
                            <p class="text-gray-500">{{ $order->delivery->latitude }}, {{ $order->delivery->longitude }}</p>
                        </div>
                        @if($order->delivery->delivery_instructions)
                            <div>
                                <span class="block text-gray-600 mb-1">Instructions</span>
                                <p class="text-gray-900">{{ $order->delivery->delivery_instructions }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Order Items -->
            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h2>
                @php
                    // A group order is served per person so the kitchen can bag
                    // it that way; a solo order keeps a single unlabelled list.
                    $itemGroups = $order->participants->isNotEmpty()
                        ? $order->participants->map(fn ($participant) => [
                            'label' => $participant->name,
                            'is_primary' => $participant->is_primary,
                            'items' => $order->orderItems->where('order_participant_id', $participant->id),
                        ])->push([
                            'label' => 'Unassigned',
                            'is_primary' => false,
                            'items' => $order->orderItems->whereNull('order_participant_id'),
                        ])->filter(fn (array $group) => $group['items']->isNotEmpty())
                        : collect([['label' => null, 'is_primary' => false, 'items' => $order->orderItems]]);
                @endphp

                <div class="space-y-5">
                    @forelse($itemGroups as $group)
                        <div>
                            @if($group['label'])
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                        {{ $group['label'] }}
                                        @if($group['is_primary'])
                                            <span class="ml-1 text-xs font-medium normal-case text-gray-400">· placed the order</span>
                                        @endif
                                    </p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        ${{ number_format($group['items']->sum(fn ($item) => $item->lineTotal()), 2) }}
                                    </p>
                                </div>
                            @endif

                            <div class="space-y-3">
                                @foreach($group['items'] as $item)
                                    <div class="flex items-start justify-between p-3 rounded-lg bg-gray-50 border border-gray-200">
                                        <div class="flex-1 pr-4">
                                            <p class="text-gray-900 font-medium">{{ $item->item?->name ?: 'Item' }}</p>
                                            @if($item->orderMealDeal)
                                                <p class="text-xs font-semibold" style="color: var(--color-forest)">Meal Deal: {{ $item->orderMealDeal->name }} #{{ $item->orderMealDeal->id }}</p>
                                                <div class="mt-1 text-xs text-gray-600">
                                                    <p>Deal base: {{ number_format((float) $item->orderMealDeal->base_price, 2) }} · Extras: {{ number_format((float) $item->orderMealDeal->extras_price, 2) }} · Service charge: {{ number_format((float) $item->orderMealDeal->service_charge, 2) }}</p>
                                                    @foreach($item->orderMealDeal->selections ?? [] as $group => $choices)
                                                        @if($group === 'primary_item')
                                                            <p>Item: {{ $choices['name'] ?? 'Item' }}</p>
                                                        @elseif(is_array($choices) && count($choices))
                                                            <p>{{ str_replace('_', ' ', ucfirst($group)) }}: {{ collect($choices)->pluck('name')->filter()->implode(', ') }}</p>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                            <p class="text-sm text-gray-600">Qty: {{ $item->quantity }}</p>

                                            {{-- The options are what the kitchen actually builds, and on
                                                 an options-priced item they carry the whole cost. --}}
                                            @if($item->orderItemOptions->isNotEmpty())
                                                <ul class="mt-2 space-y-1">
                                                    @foreach($item->orderItemOptions as $option)
                                                        <li class="text-sm text-gray-600 flex justify-between gap-4">
                                                            <span>
                                                                @if($option->optionValue?->option?->name)
                                                                    <span class="text-gray-400">{{ $option->optionValue->option->name }}:</span>
                                                                @endif
                                                                {{ $option->optionValue?->name ?: 'Option' }}
                                                                @if(($option->qty ?? 1) > 1)
                                                                    <span class="text-gray-400">x{{ $option->qty }}</span>
                                                                @endif
                                                            </span>
                                                            @if((float) $option->price > 0)
                                                                <span class="text-gray-500 whitespace-nowrap">
                                                                    +${{ number_format($option->price * max(1, (int) ($option->qty ?? 1)), 2) }}
                                                                </span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif

                                            @if($item->comment)
                                                <p class="text-sm text-gray-500 mt-2">Notes: {{ $item->comment }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <p class="text-gray-900 font-semibold">${{ number_format($item->lineTotal(), 2) }}</p>
                                            @if($item->quantity > 1)
                                                <p class="text-xs text-gray-600">${{ number_format($item->lineTotal() / $item->quantity, 2) }} each</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-500">
                            No items in this order
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Payments -->
            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Payments</h2>
                        @if($order->payments->isNotEmpty())
                            <p class="mt-1 text-sm text-gray-600">
                                ${{ number_format($order->payments->sum('amount'), 2) }} recorded across {{ $order->payments->count() }} {{ \Illuminate\Support\Str::plural('payment', $order->payments->count()) }}
                            </p>
                        @else
                            <p class="mt-1 text-sm text-gray-600">No payment has been recorded for this order.</p>
                        @endif
                    </div>
                    <button type="button" class="btn btn-primary shrink-0 justify-center" @click.stop="openPaymentModal($event)">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                        </svg>
                        Add Payment
                    </button>
                </div>

                @if($order->payments->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($order->payments as $payment)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 border border-gray-200">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[var(--color-cream)] text-[var(--color-forest)]" aria-hidden="true">
                                        @if($payment->method === 'card')
                                            <i class="bi bi-credit-card text-lg"></i>
                                        @else
                                            <i class="bi bi-cash-stack text-lg"></i>
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-gray-900 font-medium">
                                            {{ ucfirst($payment->method ?? 'Payment') }}
                                            @if($payment->method === 'card' && $payment->card_last_four)
                                                <span class="font-normal text-gray-500">ending in {{ $payment->card_last_four }}</span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-600">{{ $payment->created_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                                <p class="ml-4 shrink-0 text-gray-900 font-semibold tabular-nums">${{ number_format($payment->amount, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-[var(--color-cream-dark)] bg-[var(--color-cream)] px-5 py-6 text-center">
                        <p class="text-sm text-[var(--color-ink-light)]">Use “Add Payment” to record cash or card received.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right column: Order Summary -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl p-6 sticky top-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Order Summary</h2>

                <div class="space-y-4 pb-6 border-b border-gray-200">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="text-gray-900 font-semibold">${{ number_format($order->subtotal, 2) }}</span>
                    </div>

                    @if($order->discount > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Discount</span>
                            <span class="text-red-600">-${{ number_format($order->discount, 2) }}</span>
                        </div>
                    @endif

                    @if($order->discount_percent > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Discount %</span>
                            <span class="text-red-600">-{{ number_format($order->discount_percent, 2) }}%</span>
                        </div>
                    @endif

                    @if($order->service_charge > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Service Charge</span>
                            <span class="text-gray-900 font-semibold">${{ number_format($order->service_charge, 2) }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center pt-4 mb-6">
                    <span class="text-lg font-semibold text-gray-900">Total</span>
                    <span class="text-2xl font-bold" style="color: var(--color-sage);">${{ number_format($order->total, 2) }}</span>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Order Date:</span>
                        <span>{{ $order->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Order Time:</span>
                        <span>{{ $order->created_at->format('H:i') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Order #:</span>
                        <span class="font-mono">#{{ $order->number }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="mt-6 pt-6 border-t border-gray-200 space-y-3">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label" for="status_id">Status</label>
                        <select id="status_id" name="status_id" class="form-select @error('status_id') error @enderror" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status->id }}" @selected((int) old('status_id', $order->status_id) === $status->id)>
                                    {{ ucfirst($status->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full btn btn-primary btn-forest justify-center">
                        Update Status
                    </button>
                </form>

                @if(auth()->user()->role_id === 1)
                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" class="mt-6" onsubmit="return confirm('Delete this order? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2.5 text-sm font-medium text-white rounded-lg bg-red-600/80 hover:bg-red-600 transition-colors">
                            Delete Order
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <teleport to="body">
        <div
            v-show="paymentModalOpen"
            v-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-labelledby="payment-modal-title"
            @keydown.esc="closePaymentModal()"
            @keydown.tab="trapPaymentFocus($event)"
        >
            <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
                <div class="fixed inset-0 bg-black/60" aria-hidden="true" @click="closePaymentModal()"></div>

                <div id="payment-dialog" class="modal-card relative max-w-lg" @click.stop>
                    <form method="POST" action="{{ route('admin.orders.payments.store', $order) }}" v-on:submit="paymentSubmitting = true">
                        @csrf

                        <div class="modal-header">
                            <div>
                                <h2 id="payment-modal-title" class="modal-title">Add Payment</h2>
                                <p class="mt-1 text-sm text-[var(--color-ink-light)]">Record a payment for order #{{ $order->number }}.</p>
                            </div>
                            <button type="button" class="modal-close" aria-label="Close payment form" :disabled="paymentSubmitting" @click="closePaymentModal()">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="modal-body space-y-6">
                            <div class="flex items-center justify-between rounded-xl bg-[var(--color-cream)] px-4 py-3">
                                <span class="text-sm text-[var(--color-ink-light)]">Order total</span>
                                <span class="heading-serif text-xl font-semibold text-[var(--color-forest)] tabular-nums">${{ number_format($order->total, 2) }}</span>
                            </div>

                            <fieldset>
                                <legend class="mb-2 block text-sm font-medium text-[var(--color-ink)]">Payment method</legend>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="cursor-pointer rounded-xl border p-4 transition-colors has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-[rgba(124,154,138,0.15)]" :class="payment.method === 'cash' ? 'border-[var(--color-forest)] bg-[var(--color-cream)]' : 'border-[var(--color-cream-dark)] bg-white hover:bg-[var(--color-cream)]'">
                                        <input v-model="payment.method" class="sr-only" type="radio" name="method" value="cash">
                                        <span class="flex items-center gap-3">
                                            <i class="bi bi-cash-stack text-xl text-[var(--color-forest)]" aria-hidden="true"></i>
                                            <span class="text-sm font-semibold text-[var(--color-ink)]">Cash</span>
                                        </span>
                                    </label>
                                    <label class="cursor-pointer rounded-xl border p-4 transition-colors has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-[rgba(124,154,138,0.15)]" :class="payment.method === 'card' ? 'border-[var(--color-forest)] bg-[var(--color-cream)]' : 'border-[var(--color-cream-dark)] bg-white hover:bg-[var(--color-cream)]'">
                                        <input v-model="payment.method" class="sr-only" type="radio" name="method" value="card">
                                        <span class="flex items-center gap-3">
                                            <i class="bi bi-credit-card text-xl text-[var(--color-forest)]" aria-hidden="true"></i>
                                            <span class="text-sm font-semibold text-[var(--color-ink)]">Card</span>
                                        </span>
                                    </label>
                                </div>
                                @error('method', 'payment')
                                    <p class="mt-2 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </fieldset>

                            <div>
                                <label for="payment-amount" class="mb-2 block text-sm font-medium text-[var(--color-ink)]">Amount</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 font-semibold text-[var(--color-ink-light)]">$</span>
                                    <input
                                        id="payment-amount"
                                        v-model="payment.amount"
                                        name="amount"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        inputmode="decimal"
                                        class="w-full rounded-xl border border-[var(--color-cream-dark)] bg-[var(--color-cream)] py-3 pl-8 pr-4 text-[var(--color-ink)] tabular-nums focus:border-[var(--color-sage)] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[rgba(124,154,138,0.15)] @error('amount', 'payment') border-[var(--color-error)] bg-[var(--color-error-light)] @enderror"
                                        required
                                    >
                                </div>
                                @error('amount', 'payment')
                                    <p class="mt-1.5 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </div>

                            <div v-if="payment.method === 'card'">
                                <label for="card-last-four" class="mb-2 block text-sm font-medium text-[var(--color-ink)]">Last four digits</label>
                                <input
                                    id="card-last-four"
                                    v-model="payment.cardLastFour"
                                    name="card_last_four"
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]{4}"
                                    maxlength="4"
                                    autocomplete="off"
                                    placeholder="4242"
                                    class="w-full rounded-xl border border-[var(--color-cream-dark)] bg-[var(--color-cream)] px-4 py-3 text-[var(--color-ink)] tabular-nums placeholder:text-[var(--color-ink-light)] focus:border-[var(--color-sage)] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[rgba(124,154,138,0.15)] @error('card_last_four', 'payment') border-[var(--color-error)] bg-[var(--color-error-light)] @enderror"
                                    :required="payment.method === 'card'"
                                >
                                <p class="mt-1.5 text-xs text-[var(--color-ink-light)]">Only the final four digits are stored—never the full card number.</p>
                                @error('card_last_four', 'payment')
                                    <p class="mt-1.5 text-sm text-[var(--color-error)]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" :disabled="paymentSubmitting" @click="closePaymentModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary min-w-36 justify-center disabled:cursor-wait disabled:opacity-70" :disabled="paymentSubmitting">
                                <span v-text="paymentSubmitting ? 'Adding…' : 'Add Payment'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </teleport>
</div>
@endsection
