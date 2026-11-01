@extends('admin.layouts.app')

@section('title', 'Session #' . $session->id)
@section('header', 'Session #' . $session->id)

@push('scripts')
<script>
    window.AdminVuePage = () => ({
        closeSessionModalOpen: @js($errors->any() && old('form_action') === 'close'),
        closingAmount: @js(old('closing_amount', '')),
        modalTrigger: null,
        isSubmitting: false,
        openCloseSessionModal(event) {
            this.modalTrigger = event?.currentTarget || null;
            document.getElementById('admin-vue-app').inert = true;
            this.closeSessionModalOpen = true;
            this.$nextTick(() => document.querySelector('#close-session-dialog input[name="closing_amount"]')?.focus());
        },
        closeCloseSessionModal() {
            this.closeSessionModalOpen = false;
            document.getElementById('admin-vue-app').inert = false;
            this.$nextTick(() => this.modalTrigger?.focus());
        },
        trapCloseSessionFocus(event) {
            const dialog = document.getElementById('close-session-dialog');
            const controls = [...dialog.querySelectorAll('button, input')]
                .filter(element => !element.disabled && element.getClientRects().length);
            if (!controls.length) return;
            const first = controls[0], last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        },
        formatCurrency(value) {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value) || 0);
        },
        closingVariance() {
            const expected = @js($session->expected_amount === null ? null : (float) $session->expected_amount);
            if (expected === null || this.closingAmount === '') return null;
            const amount = Number(this.closingAmount);
            return Number.isFinite(amount) ? amount - expected : null;
        },
        initPage() {
            document.getElementById('admin-vue-app').inert = this.closeSessionModalOpen;
            if (this.closeSessionModalOpen) {
                this.$nextTick(() => document.querySelector('#close-session-dialog [aria-invalid="true"], #close-session-dialog input[name="closing_amount"]')?.focus());
            }
        },
    });
</script>
@endpush

@section('content')
<div class="animate-in">
    <div class="page-header animate-in mb-6">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.sessions.index') }}" class="text-white/60 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Session #{{ $session->id }}</h1>
                    <p style="color: var(--color-sage-light); opacity: 0.9;">
                        Opened {{ $session->opened_at?->format('M d, Y H:i') ?? $session->created_at->format('M d, Y H:i') }}
                    </p>
                </div>
            </div>
            @php
                $statusColor = $session->is_open
                    ? ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50']
                    : ['bg' => 'rgba(96, 125, 139, 0.2)', 'text' => '#607D8B'];
            @endphp
            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-block px-4 py-2 text-sm font-semibold rounded-lg" style="background: {{ $statusColor['bg'] }}; color: {{ $statusColor['text'] }};">
                    {{ $session->is_open ? 'Open' : 'Closed' }}
                </div>
                @if($session->is_open)
                    <button type="button" class="btn btn-primary btn-forest" @click="openCloseSessionModal($event)">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>
                        Close session
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in animate-delay-1">
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Cashier Information</h2>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                            {{ strtoupper(substr($session->user?->firstname ?? 'U', 0, 1) . substr($session->user?->lastname ?? 'S', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-gray-900 font-semibold">{{ $session->user?->full_name ?? 'Unknown Cashier' }}</p>
                            @if($session->user?->username)
                                <p class="text-sm text-gray-600">{{ $session->user->username }}</p>
                            @endif
                            @if($session->user?->email)
                                <p class="text-sm text-gray-600">{{ $session->user->email }}</p>
                            @endif
                            <p class="mt-1 text-sm font-medium text-[var(--color-forest)]">{{ $session->branch?->name ?? 'No branch assigned' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Session Activity</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Opened At</span>
                        <span class="text-gray-900 font-semibold">{{ $session->opened_at?->format('M d, Y H:i') ?? $session->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Closed At</span>
                        <span class="text-gray-900 font-semibold">{{ $session->closed_at?->format('M d, Y H:i') ?? 'Still open' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Orders</span>
                        <span class="text-gray-900 font-semibold">{{ $session->orders->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Withdrawals</span>
                        <span class="text-gray-900 font-semibold">{{ $session->withdrawals->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Orders</h2>
                <div class="space-y-3">
                    @forelse($session->orders as $order)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 border border-gray-200">
                            <div>
                                <p class="text-gray-900 font-medium">Order #{{ $order->number }}</p>
                                <p class="text-sm text-gray-600">{{ $order->customer?->name ?: 'Walk-in' }}</p>
                                <p class="text-xs text-gray-500">{{ $order->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                @php
                                    $orderStatusColor = match($order->status?->code ?? 'pending') {
                                        'pending' => ['bg' => 'rgba(255, 193, 7, 0.2)', 'text' => '#FFC107'],
                                        'confirmed' => ['bg' => 'rgba(33, 150, 243, 0.2)', 'text' => '#2196F3'],
                                        'preparing' => ['bg' => 'rgba(156, 39, 176, 0.2)', 'text' => '#9C27B0'],
                                        'ready' => ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50'],
                                        'completed' => ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50'],
                                        'cancelled' => ['bg' => 'rgba(244, 67, 54, 0.2)', 'text' => '#F44336'],
                                        default => ['bg' => 'rgba(124, 154, 138, 0.2)', 'text' => 'var(--color-sage)']
                                    };
                                @endphp
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full mb-2" style="background: {{ $orderStatusColor['bg'] }}; color: {{ $orderStatusColor['text'] }};">
                                    {{ $order->status?->name ?? 'Unknown' }}
                                </span>
                                <p class="text-gray-900 font-semibold">${{ number_format($order->total, 2) }}</p>
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-xs text-slate-500 hover:text-slate-700">View order</a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-500">
                            No orders linked to this session
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Withdrawals</h2>
                <div class="space-y-3">
                    @forelse($session->withdrawals as $withdrawal)
                        <div class="flex items-start justify-between p-3 rounded-lg bg-gray-50 border border-gray-200">
                            <div>
                                <p class="text-gray-900 font-medium">{{ $withdrawal->description ?: 'Withdrawal' }}</p>
                                <p class="text-sm text-gray-600">{{ $withdrawal->user?->full_name ?? 'Unknown user' }}</p>
                                <p class="text-xs text-gray-500">{{ $withdrawal->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-gray-900 font-semibold">-${{ number_format($withdrawal->amount, 2) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-500">
                            No withdrawals for this session
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="rounded-2xl p-6 sticky top-6 bg-white border border-gray-200 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Session Summary</h2>

                <div class="space-y-4 pb-6 border-b border-gray-200">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Opening Amount</span>
                        <span class="text-gray-900 font-semibold">${{ number_format($session->opening_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Sales</span>
                        <span class="text-gray-900 font-semibold">${{ number_format($session->total_sales, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Tips</span>
                        <span class="text-gray-900 font-semibold">${{ number_format($session->total_tips, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Service Charge</span>
                        <span class="text-gray-900 font-semibold">${{ number_format($session->total_service_charge, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Withdrawals</span>
                        <span class="text-gray-900 font-semibold">-${{ number_format($session->total_withdrawals, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Expected Amount</span>
                        <span class="text-gray-900 font-semibold">{{ $session->expected_amount !== null ? '$' . number_format($session->expected_amount, 2) : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Closing Amount</span>
                        <span class="text-gray-900 font-semibold">{{ $session->closing_amount !== null ? '$' . number_format($session->closing_amount, 2) : '-' }}</span>
                    </div>
                </div>

                @php
                    $variance = ($session->closing_amount !== null && $session->expected_amount !== null)
                        ? $session->closing_amount - $session->expected_amount
                        : null;
                @endphp
                <div class="flex justify-between items-center pt-4 mb-6">
                    <span class="text-lg font-semibold text-gray-900">Variance</span>
                    <span class="text-2xl font-bold" style="color: {{ $variance === null ? 'var(--color-sage)' : ($variance >= 0 ? 'var(--color-forest)' : 'var(--color-error)') }};">
                        {{ $variance === null ? '-' : '$' . number_format($variance, 2) }}
                    </span>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Opened Date:</span>
                        <span>{{ $session->opened_at?->format('M d, Y') ?? $session->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Opened Time:</span>
                        <span>{{ $session->opened_at?->format('H:i') ?? $session->created_at->format('H:i') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Closed Time:</span>
                        <span>{{ $session->closed_at?->format('H:i') ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Session #:</span>
                        <span class="font-mono">#{{ $session->id }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($session->is_open)
        <teleport to="body">
            <div id="close-session-dialog" v-show="closeSessionModalOpen" v-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="close-session-title" @keydown.esc="closeCloseSessionModal()" @keydown.tab="trapCloseSessionFocus($event)">
                <div class="flex min-h-screen items-center justify-center p-4 sm:p-6">
                    <div class="fixed inset-0 bg-black/60" @click="closeCloseSessionModal()"></div>
                    <div class="relative w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-[0_20px_40px_rgba(0,0,0,0.2)]">
                        <form method="POST" action="{{ route('admin.sessions.close', $session) }}" @submit="isSubmitting = true">
                            @csrf
                            <input type="hidden" name="form_action" value="close"><input type="hidden" name="return_to" value="show">
                            <div class="flex items-start justify-between bg-[var(--color-forest)] px-6 py-5 text-white">
                                <div><h2 id="close-session-title" class="heading-serif text-xl font-semibold">Close session #{{ $session->id }}</h2><p class="mt-1 text-sm text-[var(--color-sage-light)]">{{ $session->user?->full_name ?? 'Unknown cashier' }} · {{ $session->branch?->name ?? 'No branch' }}</p></div>
                                <button type="button" class="rounded-lg p-2 text-white/70 hover:bg-white/10 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-white" aria-label="Close cash session form" @click="closeCloseSessionModal()"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button>
                            </div>
                            <div class="space-y-5 px-6 py-6">
                                @if($errors->any() && old('form_action') === 'close')
                                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><p class="font-semibold">The session could not be closed.</p><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                                @endif
                                @if($session->expected_amount !== null)
                                    <div class="flex items-center justify-between rounded-xl bg-[var(--color-cream)] px-4 py-3"><span class="text-sm text-gray-600">Expected cash</span><strong class="tabular-nums text-gray-900">${{ number_format((float) $session->expected_amount, 2) }}</strong></div>
                                @endif
                                <label class="block text-sm font-medium text-gray-900">Counted closing cash
                                    <span class="mt-2 flex items-center rounded-xl border border-gray-300 bg-[var(--color-cream)] focus-within:border-[var(--color-sage)] focus-within:ring-1 focus-within:ring-[var(--color-sage)]"><span class="pl-4 pr-2 text-gray-500" aria-hidden="true">$</span><input name="closing_amount" v-model="closingAmount" type="text" inputmode="decimal" required @if($errors->has('closing_amount') && old('form_action') === 'close') aria-invalid="true" @endif class="min-w-0 flex-1 border-0 bg-transparent py-3 pl-0 pr-4 text-lg font-semibold text-gray-900 tabular-nums focus:ring-0"></span>
                                </label>
                                <div v-if="closingVariance() !== null" class="flex items-center justify-between border-t border-[var(--color-cream-dark)] pt-4"><span class="text-sm font-medium text-gray-700">Variance</span><strong class="tabular-nums" :class="closingVariance() < 0 ? 'text-red-700' : 'text-[var(--color-forest)]'" v-text="`${closingVariance() >= 0 ? '+' : ''}${formatCurrency(closingVariance())}`"></strong></div>
                                <p class="text-sm text-gray-600">Closing locks this session from further POS totals. Review the counted cash before continuing.</p>
                            </div>
                            <div class="flex justify-end gap-3 border-t border-[var(--color-cream-dark)] bg-[var(--color-cream)] px-6 py-4"><button type="button" class="btn btn-secondary" @click="closeCloseSessionModal()">Keep open</button><button type="submit" class="btn btn-primary btn-forest" :disabled="isSubmitting" :aria-busy="isSubmitting" aria-live="polite" v-text="isSubmitting ? 'Closing…' : 'Close session'"></button></div>
                        </form>
                    </div>
                </div>
            </div>
        </teleport>
    @endif
</div>
@endsection
