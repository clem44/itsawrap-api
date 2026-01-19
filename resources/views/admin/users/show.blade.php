@extends('admin.layouts.app')

@section('title', 'User Details')
@section('header', 'User Details')

@section('content')
<div class="space-y-6">
    <!-- User Info -->
    <div class="bg-white shadow-sm rounded-xl p-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center">
                <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl">
                    {{ substr($user->firstname, 0, 1) }}{{ substr($user->lastname, 0, 1) }}
                </div>
                <div class="ml-5">
                    <h2 class="text-xl font-bold text-gray-900">{{ $user->full_name }}</h2>
                    <p class="text-gray-500">{{ $user->username }}</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1 {{ $user->role_id === 1 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $user->role_id === 1 ? 'Admin' : 'User' }}
                    </span>
                </div>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Email</p>
                <p class="text-sm font-medium text-gray-900">{{ $user->email ?? 'Not set' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Last Login</p>
                <p class="text-sm font-medium text-gray-900">{{ $user->last_login?->format('M d, Y H:i') ?? 'Never' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Created</p>
                <p class="text-sm font-medium text-gray-900">{{ $user->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Updated</p>
                <p class="text-sm font-medium text-gray-900">{{ $user->updated_at->format('M d, Y H:i') }}</p>
            </div>
        </div>
    </div>

    <!-- API Tokens -->
    <div class="bg-white shadow-sm rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">API Tokens</h3>
            @if($tokens->count() > 0)
                <form method="POST" action="{{ route('admin.users.revoke-all-tokens', $user) }}" onsubmit="return confirm('Are you sure you want to revoke all tokens?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-900">Revoke All</button>
                </form>
            @endif
        </div>

        @if($tokens->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Used</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($tokens as $token)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $token->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $token->created_at->format('M d, Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $token->last_used_at?->format('M d, Y H:i') ?? 'Never' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('admin.users.revoke-token', [$user, $token->id]) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 text-center py-8">No active API tokens</p>
        @endif
    </div>

    <!-- Back Button -->
    <div>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Users
        </a>
    </div>
</div>
@endsection
