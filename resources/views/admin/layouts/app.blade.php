@php
    $dataMenuOpen = request()->routeIs(
        'admin.categories.*',
        'admin.orders.*',
        'admin.statuses.*',
        'admin.sessions.*',
        'admin.tips.*',
        'admin.payments.*',
        'admin.items.*',
        'admin.options.*',
        'admin.branches.*',
        'admin.customers.*',
        'admin.media-library.*'
    );
    $deliveryMenuOpen = request()->routeIs(
        'admin.deliveries.*',
        'admin.delivery-windows.*'
    );
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - It's A Wrap API</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,600;1,9..144,400&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">

    <style>
        [v-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen" style="background: var(--color-cream);">
    <div
        id="admin-vue-app"
        v-cloak
        data-menu-open="{{ $dataMenuOpen ? 'true' : 'false' }}"
        data-delivery-menu-open="{{ $deliveryMenuOpen ? 'true' : 'false' }}"
        class="flex h-screen overflow-hidden"
    >
        <!-- Sidebar -->
        <aside class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col admin-sidebar" :class="{ 'collapsed': sidebarCollapsed }">
                <div class="flex items-center justify-between h-16 px-4 sidebar-header">
                    <span class="text-xl font-bold sidebar-logo">
                        <span class="sidebar-logo-full">It's A Wrap</span>
                        <span class="sidebar-logo-compact">IAW</span>
                    </span>
                    <button type="button" class="sidebar-collapse-btn" @click.stop="sidebarCollapsed = !sidebarCollapsed" :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                </div>
                <nav class="flex-1 px-3 py-4 overflow-y-auto relative z-10">

                    <a href="{{ route('admin.dashboard') }}" class="sidebar-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="sidebar-label">Dashboard</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="sidebar-label">Users</span>
                    </a>
                    <div class="sidebar-section">
                        <button type="button" class="sidebar-nav-link sidebar-nav-toggle" :class="{ 'active': dataMenuOpen }" @click="dataMenuOpen = !dataMenuOpen">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                            </svg>
                            <span class="sidebar-label">Data</span>
                            <svg class="w-4 h-4 sidebar-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="{ 'sidebar-chevron-open': dataMenuOpen }" v-show="!sidebarCollapsed">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="sidebar-submenu" v-show="dataMenuOpen && !sidebarCollapsed" v-cloak>
                            <a href="{{ route('admin.orders.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><span class="sidebar-label">Orders</span></a>
                            <a href="{{ route('admin.statuses.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.statuses.*') ? 'active' : '' }}"><span class="sidebar-label">Statuses</span></a>
                            <a href="{{ route('admin.sessions.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}"><span class="sidebar-label">Sessions</span></a>
                            <a href="{{ route('admin.customers.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}"><span class="sidebar-label">Customers</span></a>
                            <a href="{{ route('admin.tips.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.tips.*') ? 'active' : '' }}"><span class="sidebar-label">Tips</span></a>
                            <a href="{{ route('admin.payments.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}"><span class="sidebar-label">Payments</span></a>
                            <a href="{{ route('admin.items.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.items.*') ? 'active' : '' }}"><span class="sidebar-label">Items</span></a>
                            <a href="{{ route('admin.options.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.options.*') ? 'active' : '' }}"><span class="sidebar-label">Options</span></a>
                            <a href="{{ route('admin.categories.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><span class="sidebar-label">Categories</span></a>
                            <a href="{{ route('admin.branches.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}"><span class="sidebar-label">Branches</span></a>
                        </div>
                    </div>
                    <a href="{{ route('admin.api-docs') }}" class="sidebar-nav-link {{ request()->routeIs('admin.api-docs') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="sidebar-label">API Documentation</span>
                    </a>
                    <div class="sidebar-section">
                        <button type="button" class="sidebar-nav-link sidebar-nav-toggle" :class="{ 'active': deliveryMenuOpen }" @click="deliveryMenuOpen = !deliveryMenuOpen">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="sidebar-label">Delivery</span>
                            <svg class="w-4 h-4 sidebar-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="{ 'sidebar-chevron-open': deliveryMenuOpen }" v-show="!sidebarCollapsed">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="sidebar-submenu" v-show="deliveryMenuOpen && !sidebarCollapsed" v-cloak>
                            <a href="{{ route('admin.deliveries.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.deliveries.*') ? 'active' : '' }}"><span class="sidebar-label">Deliveries</span></a>
                            <a href="{{ route('admin.delivery-windows.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.delivery-windows.*') ? 'active' : '' }}"><span class="sidebar-label">Delivery Windows</span></a>
                        </div>
                    </div>
                    <a href="{{ route('admin.rewards.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.rewards.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m-4-4h8m-9 8h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="sidebar-label">Rewards</span>
                    </a>
                    <a href="{{ route('admin.media-library.show') }}" class="sidebar-nav-link {{ request()->routeIs('admin.media-library.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"></rect>
                            <circle cx="9" cy="9" r="2" stroke-width="2"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15l-3.5-3.5a2 2 0 00-2.8 0L6 20"></path>
                        </svg>
                        <span class="sidebar-label">Media Library</span>
                    </a>
                    <a href="{{ route('admin.reports') }}" class="sidebar-nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="sidebar-label">Reports</span>
                    </a>
                    <a href="{{ route('admin.downloads.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.downloads.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0 0l-4-4m4 4l4-4"></path>
                        </svg>
                        <span class="sidebar-label">Downloads</span>
                    </a>

                </nav>
                <div class="flex-shrink-0 p-4 sidebar-footer relative z-10">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="sidebar-avatar">
                                {{ substr(auth()->user()->firstname, 0, 1) }}{{ substr(auth()->user()->lastname, 0, 1) }}
                            </div>
                        </div>
                        <div class="ml-3 sidebar-user-meta">
                            <p class="sidebar-user-name">{{ auth()->user()->full_name }}</p>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="sidebar-signout">Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile sidebar -->
        <div v-show="sidebarOpen" v-cloak class="fixed inset-0 z-40 md:hidden">
            <div v-show="sidebarOpen" class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="sidebarOpen = false"></div>
            <div v-show="sidebarOpen" class="relative flex flex-col flex-1 w-full max-w-xs admin-sidebar">
                <div class="absolute top-0 right-0 pt-2 -mr-12">
                    <button @click="sidebarOpen = false" class="flex items-center justify-center w-10 h-10 ml-1 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="flex items-center justify-center h-16 px-4 sidebar-header">
                    <span class="text-xl font-bold sidebar-logo">It's A Wrap</span>
                </div>
                <nav class="flex-1 px-3 py-4 overflow-y-auto relative z-10">
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Users
                    </a>
                    <div class="sidebar-section">
                        <button type="button" class="sidebar-nav-link sidebar-nav-toggle" :class="{ 'active': dataMenuOpen }" @click="dataMenuOpen = !dataMenuOpen">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                            </svg>
                            Data
                            <svg class="w-4 h-4 sidebar-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="{ 'sidebar-chevron-open': dataMenuOpen }">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="sidebar-submenu" v-show="dataMenuOpen" v-cloak>
                            <a href="{{ route('admin.orders.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">Orders</a>
                            <a href="{{ route('admin.statuses.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.statuses.*') ? 'active' : '' }}">Statuses</a>
                            <a href="{{ route('admin.sessions.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}">Sessions</a>
                            <a href="{{ route('admin.customers.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">Customers</a>
                            <a href="{{ route('admin.tips.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.tips.*') ? 'active' : '' }}">Tips</a>
                            <a href="{{ route('admin.payments.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">Payments</a>
                            <a href="{{ route('admin.items.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.items.*') ? 'active' : '' }}">Items</a>
                            <a href="{{ route('admin.options.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.options.*') ? 'active' : '' }}">Options</a>
                            <a href="{{ route('admin.categories.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">Categories</a>
                            <a href="{{ route('admin.branches.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}">Branches</a>
                        </div>
                    </div>
                    <a href="{{ route('admin.api-docs') }}" class="sidebar-nav-link {{ request()->routeIs('admin.api-docs') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        API Documentation
                    </a>
                    <div class="sidebar-section">
                        <button type="button" class="sidebar-nav-link sidebar-nav-toggle" :class="{ 'active': deliveryMenuOpen }" @click="deliveryMenuOpen = !deliveryMenuOpen">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Delivery
                            <svg class="w-4 h-4 sidebar-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="{ 'sidebar-chevron-open': deliveryMenuOpen }">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="sidebar-submenu" v-show="deliveryMenuOpen" v-cloak>
                            <a href="{{ route('admin.deliveries.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.deliveries.*') ? 'active' : '' }}">Deliveries</a>
                            <a href="{{ route('admin.delivery-windows.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.delivery-windows.*') ? 'active' : '' }}">Delivery Windows</a>
                        </div>
                    </div>
                    <a href="{{ route('admin.rewards.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.rewards.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m-4-4h8m-9 8h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Rewards
                    </a>
                    <a href="{{ route('admin.media-library.show') }}" class="sidebar-nav-link {{ request()->routeIs('admin.media-library.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"></rect>
                            <circle cx="9" cy="9" r="2" stroke-width="2"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15l-3.5-3.5a2 2 0 00-2.8 0L6 20"></path>
                        </svg>
                        Media Library
                    </a>
                    <a href="{{ route('admin.reports') }}" class="sidebar-nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Reports
                    </a>
                    <a href="{{ route('admin.downloads.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.downloads.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0 0l-4-4m4 4l4-4"></path>
                        </svg>
                        Downloads
                    </a>

                </nav>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Top bar -->
            <header class="admin-header">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <button @click="sidebarOpen = true" class="md:hidden focus:outline-none" style="color: var(--color-ink-light);">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-lg">@yield('header', 'Dashboard')</h1>
                    <div class="flex items-center space-x-4">
                        <span class="admin-username">{{ auth()->user()->username }}</span>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 admin-main">
                @if(session('success'))
                    <div class="mb-4 p-4 rounded-xl border" style="background: rgba(124, 154, 138, 0.1); border-color: var(--color-sage); color: var(--color-forest);">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 p-4 rounded-xl border" style="background: var(--color-error-light); border-color: var(--color-error); color: var(--color-error);">
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @auth
        @include('admin.media-library._picker')
    @endauth
    @stack('scripts')
</body>
</html>
