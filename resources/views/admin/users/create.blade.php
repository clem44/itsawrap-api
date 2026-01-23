@extends('admin.layouts.app')

@section('title', 'Create User')
@section('header', 'Create User')

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

    <div class="form-card with-accent">
        <div class="form-header">
            <h1 class="heading-serif text-2xl font-semibold">Create New User</h1>
            <p>Add a new team member to your organization</p>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" id="createUserForm">
            @csrf

            <!-- Personal Information -->
            <div class="form-section">
                <h2 class="section-title">Personal Information</h2>

                <div class="flex justify-center mb-6">
                    <div class="avatar-preview" id="avatarPreview">
                        <span id="avatarInitials">?</span>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstname" class="form-label">First Name</label>
                        <input type="text" name="firstname" id="firstname" value="{{ old('firstname') }}" required
                               class="form-input @error('firstname') error @enderror"
                               placeholder="John">
                        @error('firstname')
                            <p class="error-message">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="lastname" class="form-label">Last Name</label>
                        <input type="text" name="lastname" id="lastname" value="{{ old('lastname') }}" required
                               class="form-input @error('lastname') error @enderror"
                               placeholder="Doe">
                        @error('lastname')
                            <p class="error-message">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Account Details -->
            <div class="form-section">
                <h2 class="section-title">Account Details</h2>

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required
                           class="form-input @error('username') error @enderror"
                           placeholder="johndoe">
                    @error('username')
                        <p class="error-message">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email <span class="optional">(Optional)</span></label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           class="form-input @error('email') error @enderror"
                           placeholder="john@example.com">
                    @error('email')
                        <p class="error-message">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <!-- Role Selection -->
            <div class="form-section">
                <h2 class="section-title">Role & Permissions</h2>

                <div class="role-cards">
                    <label class="role-card user {{ old('role_id', 2) == 2 ? 'selected' : '' }}" data-role="user">
                        <input type="radio" name="role_id" value="2" {{ old('role_id', 2) == 2 ? 'checked' : '' }}>
                        <div class="role-card-content">
                            <div class="role-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="role-card-title">User</div>
                                <div class="role-card-desc">Standard access with limited permissions</div>
                            </div>
                        </div>
                        <div class="role-check">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </label>

                    <label class="role-card admin {{ old('role_id') == 1 ? 'selected' : '' }}" data-role="admin">
                        <input type="radio" name="role_id" value="1" {{ old('role_id') == 1 ? 'checked' : '' }}>
                        <div class="role-card-content">
                            <div class="role-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="role-card-title">Administrator</div>
                                <div class="role-card-desc">Full access to all features</div>
                            </div>
                        </div>
                        <div class="role-check">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </label>
                </div>
                @error('role_id')
                    <p class="error-message mt-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Password -->
            <div class="form-section">
                <h2 class="section-title">Security</h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="password" required
                                   class="form-input @error('password') error @enderror"
                                   placeholder="Enter a secure password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg class="w-5 h-5 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="error-message">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                   class="form-input"
                                   placeholder="Repeat the password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                                <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg class="w-5 h-5 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-forest">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Avatar preview
    const firstnameInput = document.getElementById('firstname');
    const lastnameInput = document.getElementById('lastname');
    const avatarInitials = document.getElementById('avatarInitials');

    function updateAvatarPreview() {
        const first = firstnameInput.value.charAt(0).toUpperCase() || '';
        const last = lastnameInput.value.charAt(0).toUpperCase() || '';
        avatarInitials.textContent = first || last ? first + last : '?';
    }

    firstnameInput.addEventListener('input', updateAvatarPreview);
    lastnameInput.addEventListener('input', updateAvatarPreview);
    updateAvatarPreview();

    // Role card selection
    const roleCards = document.querySelectorAll('.role-card');
    roleCards.forEach(card => {
        card.addEventListener('click', () => {
            roleCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
        });
    });

    // Password toggle
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const wrapper = input.closest('.password-input-wrapper');
        const eyeOpen = wrapper.querySelector('.eye-open');
        const eyeClosed = wrapper.querySelector('.eye-closed');

        if (input.type === 'password') {
            input.type = 'text';
            eyeOpen.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
        } else {
            input.type = 'password';
            eyeOpen.classList.remove('hidden');
            eyeClosed.classList.add('hidden');
        }
    }
</script>
@endsection