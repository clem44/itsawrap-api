@extends('admin.layouts.app')

@section('title', 'Sessions')
@section('header', 'Sessions')

@push('scripts')
<script>
    window.AdminVuePage = () => ({
        openSessionModalOpen: @js($errors->any() && old('form_action') === 'open'),
        closeSessionModalOpen: @js($errors->any() && old('form_action') === 'close'),
        modalTrigger: null,
        submittingAction: null,
        closingAmount: @js(old('form_action') === 'close' ? old('closing_amount', '') : ''),
        closeSession: {
            id: @js(old('form_action') === 'close' ? old('session_id') : null),
            cashier: @js(old('form_action') === 'close' ? old('cashier_name', '') : ''),
            branch: @js(old('form_action') === 'close' ? old('branch_name', '') : ''),
            expectedAmount: @js(old('form_action') === 'close' && old('expected_amount') !== '' ? (float) old('expected_amount') : null),
            action: @js(old('form_action') === 'close' && old('session_id')
                ? route('admin.sessions.close', ['session' => old('session_id')])
                : route('admin.sessions.close', ['session' => '__ID__'])),
        },
        openOpenSessionModal(event) {
            this.modalTrigger = event?.currentTarget || null;
            document.getElementById('admin-vue-app').inert = true;
            this.openSessionModalOpen = true;
            this.focusModal('open-session-dialog', 'select[name="user_id"]');
        },
        openCloseSessionModal(session, event) {
            this.modalTrigger = event?.currentTarget || null;
            document.getElementById('admin-vue-app').inert = true;
            this.closeSession = {
                ...session,
                action: @js(route('admin.sessions.close', ['session' => '__ID__'])).replace('__ID__', session.id),
            };
            this.closingAmount = '';
            this.closeSessionModalOpen = true;
            this.focusModal('close-session-dialog', 'input[name="closing_amount"]');
        },
        closeModal(name) {
            this[name] = false;
            document.getElementById('admin-vue-app').inert = false;
            this.$nextTick(() => this.modalTrigger?.focus());
        },
        focusModal(id, selector) {
            this.$nextTick(() => document.getElementById(id)?.querySelector(selector)?.focus());
        },
        trapModalFocus(event, id) {
            const dialog = document.getElementById(id);
            const controls = [...dialog.querySelectorAll('button, input, select')]
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
            if (this.closeSession.expectedAmount === null || this.closingAmount === '') return null;
            const amount = Number(this.closingAmount);
            return Number.isFinite(amount) ? amount - Number(this.closeSession.expectedAmount) : null;
        },
        initPage() {
            document.getElementById('admin-vue-app').inert = this.openSessionModalOpen || this.closeSessionModalOpen;
            if (this.openSessionModalOpen) this.focusModal('open-session-dialog', '[aria-invalid="true"], select[name="user_id"]');
            if (this.closeSessionModalOpen) this.focusModal('close-session-dialog', '[aria-invalid="true"], input[name="closing_amount"]');
        },
    });
</script>
@endpush

@section('content')
<div>
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Cash Sessions</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Open the register, review live totals, and close the count with confidence.</p>
            </div>
            <button type="button" class="btn btn-primary session-open-action" @click="openOpenSessionModal($event)">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-6-6h12"/></svg>
                Open session
            </button>
        </div>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <div class="overflow-x-auto">
        <table class="users-table min-w-[960px]">
            <thead>
                <tr>
                    <th>Session</th>
                    <th>Cashier</th>
                    <th>Opening</th>
                    <th>Total Sales</th>
                    <th>Expected</th>
                    <th>Status</th>
                    <th>Opened</th>
                    <th>Closed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                                    {{ strtoupper(substr($session->user?->firstname ?? 'U', 0, 1) . substr($session->user?->lastname ?? 'S', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="user-name">Session #{{ $session->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="user-meta font-medium">{{ $session->user?->full_name ?? 'Unknown' }}</span>
                            <span class="mt-1 block text-xs text-gray-500">{{ $session->branch?->name ?? 'No branch' }}</span>
                        </td>
                        <td>
                            <span class="user-meta font-semibold">${{ number_format($session->opening_amount, 2) }}</span>
                        </td>
                        <td>
                            <span class="user-meta">${{ number_format($session->total_sales, 2) }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $session->expected_amount !== null ? '$' . number_format($session->expected_amount, 2) : '-' }}</span>
                        </td>
                        <td>
                            @php
                                $statusColor = $session->is_open
                                    ? ['bg' => 'rgba(76, 175, 80, 0.2)', 'text' => '#4CAF50']
                                    : ['bg' => 'rgba(96, 125, 139, 0.2)', 'text' => '#607D8B'];
                            @endphp
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full" style="background: {{ $statusColor['bg'] }}; color: {{ $statusColor['text'] }};">
                                {{ $session->is_open ? 'Open' : 'Closed' }}
                            </span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $session->opened_at?->format('M d, Y H:i') ?? $session->created_at->format('M d, Y H:i') }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $session->closed_at?->format('M d, Y H:i') ?? '-' }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                @if($session->is_open)
                                    <button
                                        type="button"
                                        class="action-btn edit"
                                        title="Close Session"
                                        aria-label="Close session #{{ $session->id }}"
                                        @click.stop="openCloseSessionModal(@js([
                                            'id' => $session->id,
                                            'cashier' => $session->user?->full_name ?? 'Unknown cashier',
                                            'branch' => $session->branch?->name ?? 'No branch',
                                            'expectedAmount' => $session->expected_amount === null ? null : (float) $session->expected_amount,
                                        ]), $event)"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>
                                    </button>
                                @endif
                                <a
                                    href="{{ route('admin.sessions.show', $session) }}"
                                    class="action-btn view"
                                    title="View Session"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                @if(auth()->user()->role_id === 1 && $session->is_open)
                                    <form method="POST" action="{{ route('admin.sessions.destroy', $session) }}" class="inline" onsubmit="return confirm('Delete this open session? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn delete" title="Delete Session">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7V3m6 4V3m-9 8h12M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No sessions yet</h3>
                                <p class="empty-state-text">Cash sessions will appear here once opened.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    @if($sessions->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $sessions->links('admin.partials.pagination') }}
        </div>
    @endif

    <teleport to="body">
        <div id="open-session-dialog" v-show="openSessionModalOpen" v-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="open-session-title" @keydown.esc="closeModal('openSessionModalOpen')" @keydown.tab="trapModalFocus($event, 'open-session-dialog')">
            <div class="flex min-h-screen items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-black/60" @click="closeModal('openSessionModalOpen')"></div>
                <div class="relative w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-[0_20px_40px_rgba(0,0,0,0.2)]">
                    <form method="POST" action="{{ route('admin.sessions.store') }}" @submit="submittingAction = 'open'">
                        @csrf
                        <input type="hidden" name="form_action" value="open">
                        <div class="flex items-start justify-between bg-[var(--color-forest)] px-6 py-5 text-white">
                            <div><h2 id="open-session-title" class="heading-serif text-xl font-semibold">Open cash session</h2><p class="mt-1 text-sm text-[var(--color-sage-light)]">Assign the register and record the opening float.</p></div>
                            <button type="button" class="rounded-lg p-2 text-white/70 hover:bg-white/10 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-white" aria-label="Close open session form" @click="closeModal('openSessionModalOpen')"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button>
                        </div>
                        <div class="space-y-5 px-6 py-6">
                            @if($errors->any() && old('form_action') === 'open')
                                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><p class="font-semibold">The session could not be opened.</p><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                            @endif
                            <label class="block text-sm font-medium text-gray-900">Cashier
                                <select name="user_id" required @if($errors->has('user_id') && old('form_action') === 'open') aria-invalid="true" @endif class="mt-2 w-full rounded-xl border-gray-300 bg-[var(--color-cream)] px-4 py-3 text-gray-900 focus:border-[var(--color-sage)] focus:ring-[var(--color-sage)]">
                                    <option value="">Choose a cashier</option>
                                    @foreach($cashiers as $cashier)
                                        <option value="{{ $cashier->id }}" @selected((string) old('user_id') === (string) $cashier->id) @disabled($cashier->has_open_session)>{{ $cashier->full_name }}{{ $cashier->has_open_session ? ' — session already open' : '' }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block text-sm font-medium text-gray-900">Branch
                                <select name="branch_id" required @if($errors->has('branch_id') && old('form_action') === 'open') aria-invalid="true" @endif class="mt-2 w-full rounded-xl border-gray-300 bg-[var(--color-cream)] px-4 py-3 text-gray-900 focus:border-[var(--color-sage)] focus:ring-[var(--color-sage)]">
                                    <option value="">Choose a branch</option>
                                    @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) old('branch_id', $branches->count() === 1 ? $branch->id : '') === (string) $branch->id)>{{ $branch->name }}</option>@endforeach
                                </select>
                            </label>
                            <label class="block text-sm font-medium text-gray-900">Opening cash
                                <span class="mt-2 flex items-center rounded-xl border border-gray-300 bg-[var(--color-cream)] focus-within:border-[var(--color-sage)] focus-within:ring-1 focus-within:ring-[var(--color-sage)]"><span class="pl-4 pr-2 text-gray-500" aria-hidden="true">$</span><input name="opening_amount" value="{{ old('opening_amount', '0.00') }}" type="text" inputmode="decimal" required @if($errors->has('opening_amount') && old('form_action') === 'open') aria-invalid="true" @endif class="min-w-0 flex-1 border-0 bg-transparent py-3 pl-0 pr-4 text-gray-900 tabular-nums focus:ring-0"></span>
                                <span class="mt-2 block text-xs text-gray-500">The session opens immediately using the current date and time.</span>
                            </label>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-[var(--color-cream-dark)] bg-[var(--color-cream)] px-6 py-4"><button type="button" class="btn btn-secondary" @click="closeModal('openSessionModalOpen')">Cancel</button><button type="submit" class="btn btn-primary session-open-action" :disabled="submittingAction === 'open'" :aria-busy="submittingAction === 'open'" aria-live="polite" v-text="submittingAction === 'open' ? 'Opening…' : 'Open session'"></button></div>
                    </form>
                </div>
            </div>
        </div>

        <div id="close-session-dialog" v-show="closeSessionModalOpen" v-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="close-session-title" @keydown.esc="closeModal('closeSessionModalOpen')" @keydown.tab="trapModalFocus($event, 'close-session-dialog')">
            <div class="flex min-h-screen items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-black/60" @click="closeModal('closeSessionModalOpen')"></div>
                <div class="relative w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-[0_20px_40px_rgba(0,0,0,0.2)]">
                    <form method="POST" :action="closeSession.action" @submit="submittingAction = 'close'">
                        @csrf
                        <input type="hidden" name="form_action" value="close"><input type="hidden" name="return_to" value="index"><input type="hidden" name="session_id" :value="closeSession.id"><input type="hidden" name="cashier_name" :value="closeSession.cashier"><input type="hidden" name="branch_name" :value="closeSession.branch"><input type="hidden" name="expected_amount" :value="closeSession.expectedAmount ?? ''">
                        <div class="flex items-start justify-between bg-[var(--color-forest)] px-6 py-5 text-white">
                            <div><h2 id="close-session-title" class="heading-serif text-xl font-semibold">Close session</h2><p class="mt-1 text-sm text-[var(--color-sage-light)]"><span v-text="closeSession.cashier"></span> · <span v-text="closeSession.branch"></span></p></div>
                            <button type="button" class="rounded-lg p-2 text-white/70 hover:bg-white/10 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-white" aria-label="Close cash session form" @click="closeModal('closeSessionModalOpen')"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg></button>
                        </div>
                        <div class="space-y-5 px-6 py-6">
                            @if($errors->any() && old('form_action') === 'close')<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"><p class="font-semibold">The session could not be closed.</p><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                            <div v-if="closeSession.expectedAmount !== null" class="flex items-center justify-between rounded-xl bg-[var(--color-cream)] px-4 py-3"><span class="text-sm text-gray-600">Expected cash</span><strong class="tabular-nums text-gray-900" v-text="formatCurrency(closeSession.expectedAmount)"></strong></div>
                            <label class="block text-sm font-medium text-gray-900">Counted closing cash
                                <span class="mt-2 flex items-center rounded-xl border border-gray-300 bg-[var(--color-cream)] focus-within:border-[var(--color-sage)] focus-within:ring-1 focus-within:ring-[var(--color-sage)]"><span class="pl-4 pr-2 text-gray-500" aria-hidden="true">$</span><input name="closing_amount" v-model="closingAmount" type="text" inputmode="decimal" required @if($errors->has('closing_amount') && old('form_action') === 'close') aria-invalid="true" @endif class="min-w-0 flex-1 border-0 bg-transparent py-3 pl-0 pr-4 text-lg font-semibold text-gray-900 tabular-nums focus:ring-0"></span>
                            </label>
                            <div v-if="closingVariance() !== null" class="flex items-center justify-between border-t border-[var(--color-cream-dark)] pt-4"><span class="text-sm font-medium text-gray-700">Variance</span><strong class="tabular-nums" :class="closingVariance() < 0 ? 'text-red-700' : 'text-[var(--color-forest)]'" v-text="`${closingVariance() >= 0 ? '+' : ''}${formatCurrency(closingVariance())}`"></strong></div>
                            <p class="text-sm text-gray-600">Closing locks this session from further POS totals. Review the counted cash before continuing.</p>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-[var(--color-cream-dark)] bg-[var(--color-cream)] px-6 py-4"><button type="button" class="btn btn-secondary" @click="closeModal('closeSessionModalOpen')">Keep open</button><button type="submit" class="btn btn-primary btn-forest" :disabled="submittingAction === 'close'" :aria-busy="submittingAction === 'close'" aria-live="polite" v-text="submittingAction === 'close' ? 'Closing…' : 'Close session'"></button></div>
                    </form>
                </div>
            </div>
        </div>
    </teleport>
</div>
@endsection
