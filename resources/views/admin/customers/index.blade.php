@extends('admin.layouts.app')

@section('title', 'Customers')
@section('header', 'Customers')

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('customerManager', () => ({
            createOpen: {{ $errors->any() && old('form_action') === 'create' ? 'true' : 'false' }},
            editOpen: {{ $errors->any() && old('form_action') === 'edit' ? 'true' : 'false' }},
            editCustomer: {
                id: {{ old('form_action') === 'edit' ? (old('edit_id') ?: 'null') : 'null' }},
                name: '{{ old('form_action') === 'edit' ? addslashes(old('name', '')) : '' }}',
                firstname: '{{ old('form_action') === 'edit' ? addslashes(old('firstname', '')) : '' }}',
                lastname: '{{ old('form_action') === 'edit' ? addslashes(old('lastname', '')) : '' }}',
                phone: '{{ old('form_action') === 'edit' ? addslashes(old('phone', '')) : '' }}',
                email: '{{ old('form_action') === 'edit' ? addslashes(old('email', '')) : '' }}'
            },
            createCustomer: {
                name: '',
                firstname: '',
                lastname: '',
                phone: '',
                email: ''
            },
            editAction: '{{ route('admin.customers.update', ['customer' => '__ID__']) }}',

            openCreate() {
                this.createOpen = true;
                this.editOpen = false;
                this.createCustomer = {
                    name: '',
                    firstname: '',
                    lastname: '',
                    phone: '',
                    email: ''
                };
            },

            closeCreate() {
                this.createOpen = false;
            },

            openEdit(customer) {
                this.editCustomer = {
                    id: customer.id,
                    name: customer.name || '',
                    firstname: customer.firstname || '',
                    lastname: customer.lastname || '',
                    phone: customer.phone || '',
                    email: customer.email || ''
                };
                this.editAction = '{{ route('admin.customers.update', ['customer' => '__ID__']) }}'.replace('__ID__', customer.id);
                this.editOpen = true;
                this.createOpen = false;
            },

            closeEdit() {
                this.editOpen = false;
            },

            getEditAction() {
                return this.editAction.replace('__ID__', this.editCustomer.id);
            }
        }));
    });
</script>
@endpush

@section('content')
<div x-data="customerManager">
    <div class="page-header animate-in">
        <div class="page-header-content flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="heading-serif text-3xl font-semibold text-white mb-1">Customer Management</h1>
                <p style="color: var(--color-sage-light); opacity: 0.9;">Create, edit, and manage customers.</p>
            </div>
            <button type="button" class="btn-primary btn-forest btn" @click.stop="openCreate()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Customer
            </button>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="flex items-center gap-2">
            <label for="customer_search" class="text-sm font-medium text-gray-700">Search</label>
            <input
                id="customer_search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Name, phone, or email"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)]"
            >
            <button type="submit" class="btn-primary btn-forest btn text-sm">Go</button>
        </form>
    </div>

    <div class="users-table-container animate-in animate-delay-1">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Orders</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    @php
                        $displayName = $customer->name ?: trim($customer->firstname . ' ' . $customer->lastname);
                        $displayName = $displayName ?: 'Customer';
                    @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="user-avatar" style="background: linear-gradient(135deg, rgba(124, 154, 138, 0.2) 0%, rgba(124, 154, 138, 0.6) 100%); color: var(--color-forest);">
                                    {{ strtoupper(substr($displayName, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="user-name">{{ $displayName }}</div>
                                    @if($customer->firstname || $customer->lastname)
                                        <div class="user-meta">{{ trim($customer->firstname . ' ' . $customer->lastname) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="user-meta">{{ $customer->phone ?: '-' }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $customer->email ?: '-' }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $customer->orders_count }}</span>
                        </td>
                        <td>
                            <span class="user-meta">{{ $customer->created_at->format('M d, Y') }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a
                                    href="{{ route('admin.customers.show', $customer) }}"
                                    class="action-btn view"
                                    title="View Customer"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <button
                                    type="button"
                                    class="action-btn edit"
                                    title="Edit Customer"
                                    @click.stop="openEdit(@js($customer->only(['id', 'name', 'firstname', 'lastname', 'phone', 'email'])))"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" class="inline" onsubmit="return confirm('Delete this customer? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete" title="Delete Customer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.121 17.804A8.967 8.967 0 0112 15c2.21 0 4.243.801 5.879 2.125M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4a8 8 0 100 16 8 8 0 000-16z"></path>
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">No customers yet</h3>
                                <p class="empty-state-text">Create your first customer to get started.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
        <div class="pagination-wrapper animate-in animate-delay-2">
            {{ $customers->links() }}
        </div>
    @endif

    <!-- Create Modal -->
    <template x-teleport="body">
        <div
            x-show="createOpen"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div
                    x-show="createOpen"
                    x-transition.opacity.duration.200ms
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                    @click="closeCreate()"
                ></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div
                    x-show="createOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative inline-block w-full max-w-lg transform overflow-hidden rounded-2xl bg-[var(--color-forest)] text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                <form method="POST" action="{{ route('admin.customers.store') }}">
                    @csrf
                    <input type="hidden" name="form_action" value="create">

                    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Create Customer</h2>
                        <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="closeCreate()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-5 px-6 py-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Display Name</label>
                            <input
                                type="text"
                                name="name"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                placeholder="e.g. John Doe"
                                value="{{ old('form_action') === 'create' ? old('name') : '' }}"
                                required
                            >
                            @if(old('form_action') === 'create')
                                @error('name')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-white/80">First Name</label>
                                <input
                                    type="text"
                                    name="firstname"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('firstname') border-red-500 @enderror"
                                    value="{{ old('form_action') === 'create' ? old('firstname') : '' }}"
                                >
                                @if(old('form_action') === 'create')
                                    @error('firstname')
                                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-white/80">Last Name</label>
                                <input
                                    type="text"
                                    name="lastname"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('lastname') border-red-500 @enderror"
                                    value="{{ old('form_action') === 'create' ? old('lastname') : '' }}"
                                >
                                @if(old('form_action') === 'create')
                                    @error('lastname')
                                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Phone</label>
                            <input
                                type="text"
                                name="phone"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('phone') border-red-500 @enderror"
                                value="{{ old('form_action') === 'create' ? old('phone') : '' }}"
                            >
                            @if(old('form_action') === 'create')
                                @error('phone')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Email</label>
                            <input
                                type="email"
                                name="email"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('email') border-red-500 @enderror"
                                value="{{ old('form_action') === 'create' ? old('email') : '' }}"
                            >
                            @if(old('form_action') === 'create')
                                @error('email')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-white/10 px-6 py-4">
                        <button type="button" class="rounded-lg border border-white/20 bg-transparent px-5 py-2.5 text-sm font-medium text-white hover:bg-white/10 transition-colors" @click="closeCreate()">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-dark)] transition-colors">
                            Create Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>

    <!-- Edit Modal -->
    <template x-teleport="body">
        <div
            x-show="editOpen"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div
                    x-show="editOpen"
                    x-transition.opacity.duration.200ms
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                    @click="closeEdit()"
                ></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div
                    x-show="editOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative inline-block w-full max-w-lg transform overflow-hidden rounded-2xl bg-[var(--color-forest)] text-left align-bottom shadow-xl sm:my-8 sm:align-middle"
                    @click.stop
                >
                <form method="POST" :action="getEditAction()">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_action" value="edit">
                    <input type="hidden" name="edit_id" :value="editCustomer.id">

                    <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
                        <h2 class="text-xl font-semibold text-white">Edit Customer</h2>
                        <button type="button" class="rounded-lg p-2 text-white/60 hover:bg-white/10 hover:text-white transition-colors" @click="closeEdit()">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-5 px-6 py-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Display Name</label>
                            <input
                                type="text"
                                name="name"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('name') border-red-500 @enderror"
                                x-model="editCustomer.name"
                                required
                            >
                            @if(old('form_action') === 'edit')
                                @error('name')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-white/80">First Name</label>
                                <input
                                    type="text"
                                    name="firstname"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('firstname') border-red-500 @enderror"
                                    x-model="editCustomer.firstname"
                                >
                                @if(old('form_action') === 'edit')
                                    @error('firstname')
                                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-white/80">Last Name</label>
                                <input
                                    type="text"
                                    name="lastname"
                                    class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('lastname') border-red-500 @enderror"
                                    x-model="editCustomer.lastname"
                                >
                                @if(old('form_action') === 'edit')
                                    @error('lastname')
                                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Phone</label>
                            <input
                                type="text"
                                name="phone"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('phone') border-red-500 @enderror"
                                x-model="editCustomer.phone"
                            >
                            @if(old('form_action') === 'edit')
                                @error('phone')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-white/80">Email</label>
                            <input
                                type="email"
                                name="email"
                                class="w-full rounded-lg border border-white/20 bg-white/5 px-4 py-2.5 text-white placeholder-white/40 focus:border-[var(--color-sage)] focus:outline-none focus:ring-1 focus:ring-[var(--color-sage)] @error('email') border-red-500 @enderror"
                                x-model="editCustomer.email"
                            >
                            @if(old('form_action') === 'edit')
                                @error('email')
                                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-white/10 px-6 py-4">
                        <button type="button" class="rounded-lg border border-white/20 bg-transparent px-5 py-2.5 text-sm font-medium text-white hover:bg-white/10 transition-colors" @click="closeEdit()">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-[var(--color-forest)] px-5 py-2.5 text-sm font-medium text-white hover:bg-[var(--color-forest-dark)] transition-colors">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
