@extends('admin.layouts.app')

@section('title', 'Edit User')
@section('header', 'Edit User')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,600;1,9..144,400&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="form-container animate-in">
    <a href="{{ route('admin.users.index') }}" class="back-link">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to Users
    </a>

    <user-form
        mode="edit"
        action="{{ route('admin.users.update', $user) }}"
        back-url="{{ route('admin.users.index') }}"
        :csrf-token="csrfToken"
        :roles="@js($roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name, 'code' => $role->code, 'description' => $role->description])->values())"
        :user="@js(['role_id' => (int) old('role_id', $user->role_id), 'firstname' => old('firstname', $user->firstname), 'lastname' => old('lastname', $user->lastname), 'username' => old('username', $user->username), 'email' => old('email', $user->email)])"
        :errors="@js($errors->messages())"
    ></user-form>
</div>
@endsection
