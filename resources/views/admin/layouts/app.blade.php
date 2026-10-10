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
        'admin.bundles.*',
        'admin.meal-deals.*'
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
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </button>
                </div>
                <nav class="flex-1 px-3 py-4 overflow-y-auto relative z-10">

                    <a href="{{ route('admin.dashboard') }}" class="sidebar-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        <span class="sidebar-label">Dashboard</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="bi bi-people" aria-hidden="true"></i>
                        <span class="sidebar-label">Users</span>
                    </a>
                    <div class="sidebar-section">
                        <button type="button" class="sidebar-nav-link sidebar-nav-toggle" :class="{ 'active': dataMenuOpen }" @click="dataMenuOpen = !dataMenuOpen">
                            <i class="bi bi-list-ul" aria-hidden="true"></i>
                            <span class="sidebar-label">Data</span>
                            <i class="bi bi-chevron-down sidebar-chevron" :class="{ 'sidebar-chevron-open': dataMenuOpen }" v-show="!sidebarCollapsed" aria-hidden="true"></i>
                        </button>
                        <div class="sidebar-submenu" v-show="dataMenuOpen && !sidebarCollapsed" v-cloak>
                            <a href="{{ route('admin.orders.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><span class="sidebar-label">Orders</span></a>
                            <a href="{{ route('admin.statuses.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.statuses.*') ? 'active' : '' }}"><span class="sidebar-label">Statuses</span></a>
                            <a href="{{ route('admin.sessions.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}"><span class="sidebar-label">Sessions</span></a>
                            <a href="{{ route('admin.customers.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}"><span class="sidebar-label">Customers</span></a>
                            <a href="{{ route('admin.tips.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.tips.*') ? 'active' : '' }}"><span class="sidebar-label">Tips</span></a>
                            <a href="{{ route('admin.payments.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}"><span class="sidebar-label">Payments</span></a>
                            <a href="{{ route('admin.items.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.items.*') ? 'active' : '' }}"><span class="sidebar-label">Items</span></a>
                            <a href="{{ route('admin.bundles.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.bundles.*') ? 'active' : '' }}"><span class="sidebar-label">Bundles</span></a>
                            <a href="{{ route('admin.meal-deals.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.meal-deals.*') ? 'active' : '' }}"><span class="sidebar-label">Meal Deals</span></a>
                            <a href="{{ route('admin.options.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.options.*') ? 'active' : '' }}"><span class="sidebar-label">Options</span></a>
                            <a href="{{ route('admin.categories.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><span class="sidebar-label">Categories</span></a>
                            <a href="{{ route('admin.branches.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}"><span class="sidebar-label">Branches</span></a>
                        </div>
                    </div>
                    <a href="{{ route('admin.api-docs') }}" class="sidebar-nav-link {{ request()->routeIs('admin.api-docs') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                        <span class="sidebar-label">API Documentation</span>
                    </a>
                    <div class="sidebar-section">
                        <button type="button" class="sidebar-nav-link sidebar-nav-toggle" :class="{ 'active': deliveryMenuOpen }" @click="deliveryMenuOpen = !deliveryMenuOpen">
                            <i class="bi bi-calendar-week" aria-hidden="true"></i>
                            <span class="sidebar-label">Delivery</span>
                            <i class="bi bi-chevron-down sidebar-chevron" :class="{ 'sidebar-chevron-open': deliveryMenuOpen }" v-show="!sidebarCollapsed" aria-hidden="true"></i>
                        </button>
                        <div class="sidebar-submenu" v-show="deliveryMenuOpen && !sidebarCollapsed" v-cloak>
                            <a href="{{ route('admin.deliveries.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.deliveries.*') ? 'active' : '' }}"><span class="sidebar-label">Deliveries</span></a>
                            <a href="{{ route('admin.delivery-windows.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.delivery-windows.*') ? 'active' : '' }}"><span class="sidebar-label">Delivery Windows</span></a>
                        </div>
                    </div>
                    <a href="{{ route('admin.offers.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}">
                        <i class="bi bi-tag" aria-hidden="true"></i>
                        <span class="sidebar-label">Offers</span>
                    </a>
                    <a href="{{ route('admin.rewards.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.rewards.*') ? 'active' : '' }}">
                        <i class="bi bi-patch-plus" aria-hidden="true"></i>
                        <span class="sidebar-label">Rewards</span>
                    </a>
                    <a href="{{ route('admin.hero-sliders.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.hero-sliders.*') ? 'active' : '' }}">
                       <i class="bi bi-file-slides" aria-hidden="true"></i>
                       <span class="sidebar-label">Hero Slider</span>
                    </a>
                    <a href="{{ route('admin.media-library.show') }}" class="sidebar-nav-link {{ request()->routeIs('admin.media-library.*') ? 'active' : '' }}">
                        <i class="bi bi-images" aria-hidden="true"></i>
                        <span class="sidebar-label">Media Library</span>
                    </a>
                    <a href="{{ route('admin.reports') }}" class="sidebar-nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i>
                        <span class="sidebar-label">Reports</span>
                    </a>
                    <a href="{{ route('admin.downloads.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.downloads.*') ? 'active' : '' }}">
                        <i class="bi bi-download" aria-hidden="true"></i>
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
                        <i class="bi bi-x-lg text-2xl text-white" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="flex items-center justify-center h-16 px-4 sidebar-header">
                    <span class="text-xl font-bold sidebar-logo">It's A Wrap</span>
                </div>
                <nav class="flex-1 px-3 py-4 overflow-y-auto relative z-10">
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="bi bi-people" aria-hidden="true"></i>
                        Users
                    </a>
                    <div class="sidebar-section">
                        <button type="button" class="sidebar-nav-link sidebar-nav-toggle" :class="{ 'active': dataMenuOpen }" @click="dataMenuOpen = !dataMenuOpen">
                            <i class="bi bi-list-ul" aria-hidden="true"></i>
                            Data
                            <i class="bi bi-chevron-down sidebar-chevron" :class="{ 'sidebar-chevron-open': dataMenuOpen }" aria-hidden="true"></i>
                        </button>
                        <div class="sidebar-submenu" v-show="dataMenuOpen" v-cloak>
                            <a href="{{ route('admin.orders.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">Orders</a>
                            <a href="{{ route('admin.statuses.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.statuses.*') ? 'active' : '' }}">Statuses</a>
                            <a href="{{ route('admin.sessions.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}">Sessions</a>
                            <a href="{{ route('admin.customers.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">Customers</a>
                            <a href="{{ route('admin.tips.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.tips.*') ? 'active' : '' }}">Tips</a>
                            <a href="{{ route('admin.payments.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">Payments</a>
                            <a href="{{ route('admin.items.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.items.*') ? 'active' : '' }}">Items</a>
                            <a href="{{ route('admin.bundles.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.bundles.*') ? 'active' : '' }}">Bundles</a>
                            <a href="{{ route('admin.meal-deals.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.meal-deals.*') ? 'active' : '' }}">Meal Deals</a>
                            <a href="{{ route('admin.options.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.options.*') ? 'active' : '' }}">Options</a>
                            <a href="{{ route('admin.categories.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">Categories</a>
                            <a href="{{ route('admin.branches.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}">Branches</a>
                        </div>
                    </div>
                    <a href="{{ route('admin.api-docs') }}" class="sidebar-nav-link {{ request()->routeIs('admin.api-docs') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                        API Documentation
                    </a>
                    <div class="sidebar-section">
                        <button type="button" class="sidebar-nav-link sidebar-nav-toggle" :class="{ 'active': deliveryMenuOpen }" @click="deliveryMenuOpen = !deliveryMenuOpen">
                            <i class="bi bi-calendar-week" aria-hidden="true"></i>
                            Delivery
                            <i class="bi bi-chevron-down sidebar-chevron" :class="{ 'sidebar-chevron-open': deliveryMenuOpen }" aria-hidden="true"></i>
                        </button>
                        <div class="sidebar-submenu" v-show="deliveryMenuOpen" v-cloak>
                            <a href="{{ route('admin.deliveries.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.deliveries.*') ? 'active' : '' }}">Deliveries</a>
                            <a href="{{ route('admin.delivery-windows.index') }}" class="sidebar-submenu-link {{ request()->routeIs('admin.delivery-windows.*') ? 'active' : '' }}">Delivery Windows</a>
                        </div>
                    </div>
                    <a href="{{ route('admin.offers.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}">
                        <i class="bi bi-tag" aria-hidden="true"></i>
                        Offers
                    </a>
                    <a href="{{ route('admin.rewards.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.rewards.*') ? 'active' : '' }}">
                        <i class="bi bi-patch-plus" aria-hidden="true"></i>
                        Rewards
                    </a>
                    <a href="{{ route('admin.hero-sliders.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.hero-sliders.*') ? 'active' : '' }}">
                        <i class="bi bi-file-slides" aria-hidden="true"></i>
                        Hero Slider
                    </a>
                    <a href="{{ route('admin.media-library.show') }}" class="sidebar-nav-link {{ request()->routeIs('admin.media-library.*') ? 'active' : '' }}">
                        <i class="bi bi-images" aria-hidden="true"></i>
                        Media Library
                    </a>
                    <a href="{{ route('admin.reports') }}" class="sidebar-nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i>
                        Reports
                    </a>
                    <a href="{{ route('admin.downloads.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.downloads.*') ? 'active' : '' }}">
                        <i class="bi bi-download" aria-hidden="true"></i>
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
                        <i class="bi bi-list text-2xl" aria-hidden="true"></i>
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
