@extends('admin.layouts.app')

@section('title', 'API Documentation')
@section('header', 'API Documentation')

@section('content')
<div class="space-y-6">
    <!-- Quick Links -->
    <div class="bg-white shadow-sm rounded-xl p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">API Overview</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-indigo-50 rounded-lg p-4">
                <p class="text-sm font-medium text-indigo-900">Base URL</p>
                <p class="text-sm text-indigo-700 font-mono mt-1">{{ url('/api') }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <p class="text-sm font-medium text-green-900">Authentication</p>
                <p class="text-sm text-green-700 mt-1">Bearer Token (Sanctum)</p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-4">
                <p class="text-sm font-medium text-yellow-900">Content Type</p>
                <p class="text-sm text-yellow-700 font-mono mt-1">application/json</p>
            </div>
        </div>
    </div>

    <!-- Swagger UI -->
    <div class="bg-white shadow-sm rounded-xl overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-medium text-gray-900">Interactive API Documentation</h3>
            <p class="text-sm text-gray-500 mt-1">Explore and test API endpoints</p>
        </div>
        <div class="h-screen">
            <iframe src="{{ route('l5-swagger.default.api') }}" class="w-full h-full border-0"></iframe>
        </div>
    </div>

    <!-- Endpoints Summary -->
    <div class="bg-white shadow-sm rounded-xl p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Endpoints Summary</h3>
        <div class="space-y-4">
            @php
                $endpoints = [
                    ['group' => 'Authentication', 'endpoints' => [
                        ['method' => 'POST', 'path' => '/api/login', 'description' => 'Authenticate user and get token'],
                        ['method' => 'POST', 'path' => '/api/logout', 'description' => 'Revoke current token'],
                        ['method' => 'GET', 'path' => '/api/user', 'description' => 'Get authenticated user'],
                    ]],
                    ['group' => 'Users', 'endpoints' => [
                        ['method' => 'GET', 'path' => '/api/users', 'description' => 'List all users'],
                        ['method' => 'POST', 'path' => '/api/users', 'description' => 'Create new user'],
                        ['method' => 'GET', 'path' => '/api/users/username-exists', 'description' => 'Check if username exists'],
                        ['method' => 'GET', 'path' => '/api/users/email-exists', 'description' => 'Check if email exists'],
                        ['method' => 'GET', 'path' => '/api/users/{id}', 'description' => 'Get user details'],
                        ['method' => 'PUT', 'path' => '/api/users/{id}', 'description' => 'Update user'],
                        ['method' => 'DELETE', 'path' => '/api/users/{id}', 'description' => 'Delete user'],
                    ]],
                    ['group' => 'Categories', 'endpoints' => [
                        ['method' => 'GET', 'path' => '/api/categories', 'description' => 'List all categories'],
                        ['method' => 'POST', 'path' => '/api/categories', 'description' => 'Create category'],
                        ['method' => 'GET', 'path' => '/api/categories/{id}', 'description' => 'Get category details'],
                        ['method' => 'PUT', 'path' => '/api/categories/{id}', 'description' => 'Update category'],
                        ['method' => 'DELETE', 'path' => '/api/categories/{id}', 'description' => 'Delete category'],
                    ]],
                    ['group' => 'Items', 'endpoints' => [
                        ['method' => 'GET', 'path' => '/api/items', 'description' => 'List all items'],
                        ['method' => 'POST', 'path' => '/api/items', 'description' => 'Create item'],
                        ['method' => 'GET', 'path' => '/api/items/{id}', 'description' => 'Get item with options'],
                        ['method' => 'PUT', 'path' => '/api/items/{id}', 'description' => 'Update item'],
                        ['method' => 'DELETE', 'path' => '/api/items/{id}', 'description' => 'Delete item'],
                    ]],
                    ['group' => 'Orders', 'endpoints' => [
                        ['method' => 'GET', 'path' => '/api/orders', 'description' => 'List orders'],
                        ['method' => 'POST', 'path' => '/api/orders', 'description' => 'Create order'],
                        ['method' => 'GET', 'path' => '/api/orders/{id}', 'description' => 'Get order with items'],
                        ['method' => 'PUT', 'path' => '/api/orders/{id}', 'description' => 'Update order'],
                        ['method' => 'DELETE', 'path' => '/api/orders/{id}', 'description' => 'Delete order'],
                    ]],
                    ['group' => 'Cash Sessions', 'endpoints' => [
                        ['method' => 'GET', 'path' => '/api/sessions', 'description' => 'List sessions'],
                        ['method' => 'POST', 'path' => '/api/sessions', 'description' => 'Open new session'],
                        ['method' => 'GET', 'path' => '/api/sessions/current', 'description' => 'Get current open session'],
                        ['method' => 'POST', 'path' => '/api/sessions/{id}/close', 'description' => 'Close session'],
                        ['method' => 'PATCH', 'path' => '/api/sessions/{id}/totals', 'description' => 'Update session totals'],
                    ]],
                    ['group' => 'Settings', 'endpoints' => [
                        ['method' => 'GET', 'path' => '/api/settings', 'description' => 'List all settings'],
                        ['method' => 'GET', 'path' => '/api/settings/{key}', 'description' => 'Get setting by key'],
                        ['method' => 'PUT', 'path' => '/api/settings/{key}', 'description' => 'Update setting'],
                        ['method' => 'POST', 'path' => '/api/settings/bulk', 'description' => 'Bulk update settings'],
                    ]],
                ];
            @endphp

            @foreach($endpoints as $group)
                <div x-data="{ open: false }" class="border border-gray-200 rounded-lg">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                        <span class="font-medium text-gray-900">{{ $group['group'] }}</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="p-4 space-y-2">
                        @foreach($group['endpoints'] as $endpoint)
                            <div class="flex items-center space-x-3 py-2 border-b border-gray-100 last:border-0">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                    @if($endpoint['method'] === 'GET') bg-blue-100 text-blue-800
                                    @elseif($endpoint['method'] === 'POST') bg-green-100 text-green-800
                                    @elseif($endpoint['method'] === 'PUT') bg-yellow-100 text-yellow-800
                                    @elseif($endpoint['method'] === 'DELETE') bg-red-100 text-red-800
                                    @endif">
                                    {{ $endpoint['method'] }}
                                </span>
                                <code class="text-sm text-gray-700">{{ $endpoint['path'] }}</code>
                                <span class="text-sm text-gray-500">{{ $endpoint['description'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
