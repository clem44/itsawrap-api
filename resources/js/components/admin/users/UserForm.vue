<template>
    <div>
        <div v-if="mode === 'edit'" class="user-header">
            <div class="user-header-content">
                <div class="user-avatar-large" v-text="initials"></div>
                <div class="user-header-info">
                    <h1 class="heading-serif text-2xl font-semibold" v-text="fullName || 'User'"></h1>
                    <p class="user-header-meta">Editing user profile and permissions</p>
                </div>
            </div>
        </div>

        <div class="form-card" :class="mode === 'edit' ? 'with-header' : 'with-accent'">
            <div v-if="mode === 'create'" class="form-header">
                <h1 class="heading-serif text-2xl font-semibold">Create New User</h1>
                <p>Add a new team member to your organization</p>
            </div>

            <form method="POST" :action="action">
                <input type="hidden" name="_token" :value="csrfToken">
                <input v-if="mode === 'edit'" type="hidden" name="_method" value="PUT">

                <!-- Personal Information -->
                <div class="form-section">
                    <h2 class="section-title">Personal Information</h2>

                    <div v-if="mode === 'create'" class="flex justify-center mb-6">
                        <div class="avatar-preview">
                            <span v-text="initials || '?'"></span>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="firstname" class="form-label">First Name</label>
                            <input
                                type="text"
                                name="firstname"
                                id="firstname"
                                v-model="form.firstname"
                                required
                                class="form-input"
                                :class="{ error: hasError('firstname') }"
                                placeholder="John"
                            >
                            <p v-for="message in fieldErrors('firstname')" :key="message" class="error-message">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span v-text="message"></span>
                            </p>
                        </div>

                        <div class="form-group">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input
                                type="text"
                                name="lastname"
                                id="lastname"
                                v-model="form.lastname"
                                required
                                class="form-input"
                                :class="{ error: hasError('lastname') }"
                                placeholder="Doe"
                            >
                            <p v-for="message in fieldErrors('lastname')" :key="message" class="error-message">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span v-text="message"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Account Details -->
                <div class="form-section">
                    <h2 class="section-title">Account Details</h2>

                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            v-model="form.username"
                            required
                            class="form-input"
                            :class="{ error: hasError('username') }"
                            placeholder="johndoe"
                        >
                        <p v-for="message in fieldErrors('username')" :key="message" class="error-message">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span v-text="message"></span>
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email <span class="optional">(Optional)</span></label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            v-model="form.email"
                            class="form-input"
                            :class="{ error: hasError('email') }"
                            placeholder="john@example.com"
                        >
                        <p v-for="message in fieldErrors('email')" :key="message" class="error-message">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span v-text="message"></span>
                        </p>
                    </div>
                </div>

                <!-- Role Selection -->
                <div class="form-section">
                    <h2 class="section-title">Role & Permissions</h2>

                    <div class="role-cards">
                        <label
                            v-for="role in roles"
                            :key="role.id"
                            class="role-card"
                            :class="[role.code === 'admin' ? 'admin' : 'user', { selected: form.role_id === role.id }]"
                            :for="'role_' + role.id"
                            :data-role="role.code"
                        >
                            <input
                                :id="'role_' + role.id"
                                type="radio"
                                name="role_id"
                                :value="role.id"
                                v-model="form.role_id"
                            >
                            <div class="role-card-content">
                                <div class="role-icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="roleIconPath(role)"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="role-card-title" v-text="role.name"></div>
                                    <div class="role-card-desc" v-text="role.description"></div>
                                </div>
                            </div>
                            <div class="role-check">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </label>
                    </div>
                    <p v-for="message in fieldErrors('role_id')" :key="message" class="error-message mt-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span v-text="message"></span>
                    </p>
                </div>

                <!-- Password -->
                <div class="form-section">
                    <h2 class="section-title">Security</h2>

                    <div v-if="mode === 'edit'" class="password-section-note">
                        <svg class="w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>Leave the password fields empty to keep the current password unchanged. Only fill these fields if you want to set a new password.</p>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password" class="form-label">
                                {{ mode === 'create' ? 'Password' : 'New Password' }}
                                <span v-if="mode === 'edit'" class="optional">(Optional)</span>
                            </label>
                            <div class="password-input-wrapper">
                                <input
                                    :type="passwordVisible ? 'text' : 'password'"
                                    name="password"
                                    id="password"
                                    :required="mode === 'create'"
                                    class="form-input"
                                    :class="{ error: hasError('password') }"
                                    :placeholder="mode === 'create' ? 'Enter a secure password' : 'Enter new password'"
                                >
                                <button type="button" class="password-toggle" @click="passwordVisible = !passwordVisible">
                                    <svg v-if="!passwordVisible" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                    </svg>
                                </button>
                            </div>
                            <p v-for="message in fieldErrors('password')" :key="message" class="error-message">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span v-text="message"></span>
                            </p>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <div class="password-input-wrapper">
                                <input
                                    :type="passwordConfirmVisible ? 'text' : 'password'"
                                    name="password_confirmation"
                                    id="password_confirmation"
                                    :required="mode === 'create'"
                                    class="form-input"
                                    :placeholder="mode === 'create' ? 'Repeat the password' : 'Repeat new password'"
                                >
                                <button type="button" class="password-toggle" @click="passwordConfirmVisible = !passwordConfirmVisible">
                                    <svg v-if="!passwordConfirmVisible" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a :href="backUrl" class="btn btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" :class="{ 'btn-forest': mode === 'create' }">
                        <svg v-if="mode === 'create'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ mode === 'create' ? 'Create User' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    name: 'UserForm',

    props: {
        mode: {
            type: String,
            required: true,
            validator: (value) => ['create', 'edit'].includes(value),
        },
        action: {
            type: String,
            required: true,
        },
        backUrl: {
            type: String,
            required: true,
        },
        csrfToken: {
            type: String,
            required: true,
        },
        roles: {
            type: Array,
            default: () => [],
        },
        user: {
            type: Object,
            default: () => ({}),
        },
        errors: {
            type: Object,
            default: () => ({}),
        },
    },

    data() {
        return {
            form: {
                firstname: this.user.firstname || '',
                lastname: this.user.lastname || '',
                username: this.user.username || '',
                email: this.user.email || '',
                role_id: this.user.role_id ?? null,
            },
            passwordVisible: false,
            passwordConfirmVisible: false,
        };
    },

    computed: {
        initials() {
            const first = (this.form.firstname || '').charAt(0);
            const last = (this.form.lastname || '').charAt(0);

            return (first + last).toUpperCase();
        },

        fullName() {
            return `${this.form.firstname} ${this.form.lastname}`.trim();
        },
    },

    methods: {
        fieldErrors(field) {
            return this.errors[field] || [];
        },

        hasError(field) {
            return this.fieldErrors(field).length > 0;
        },

        roleIconPath(role) {
            return role.code === 'admin'
                ? 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
                : 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z';
        },
    },
};
</script>
