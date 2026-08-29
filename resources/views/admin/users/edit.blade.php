<x-admin-layout>
    <x-slot name="header">
        Edit User Profile & Credentials — {{ $user->name }}
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Top Back Navigation -->
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition">
                <span>←</span> Back to User Dossier
            </a>

            <div class="text-xs text-slate-400 font-mono">
                User ID: #{{ $user->id }}
            </div>
        </div>

        <!-- Form 1: Profile & Account Information -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
            <div class="border-b border-slate-800 pb-4">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <span>✏️</span> User Information & Account Settings
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Update profile details, permissions, storage quota, and access status.</p>
            </div>

            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Full Name <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full text-xs sm:text-sm bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('name')
                            <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Email Address <span class="text-rose-400">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full text-xs sm:text-sm bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('email')
                            <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Phone Number
                        </label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+91 98765 43210" class="w-full text-xs sm:text-sm bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('phone')
                            <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- System Role -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            System Role <span class="text-rose-400">*</span>
                        </label>
                        <select name="role" required class="w-full text-xs sm:text-sm bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2.5 text-white focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }} {{ $role->name === 'admin' ? '(Super Administrator)' : '(Billing Operator)' }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Account Status -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Account Access Status <span class="text-rose-400">*</span>
                        </label>
                        <select name="status" required class="w-full text-xs sm:text-sm bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2.5 text-white focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>✓ Active (Normal Access)</option>
                            <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>🚫 Suspended (Blocked from Login/Actions)</option>
                        </select>
                        @error('status')
                            <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Plan Tier -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Plan Tier <span class="text-rose-400">*</span>
                        </label>
                        <select name="plan_tier" required class="w-full text-xs sm:text-sm bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2.5 text-white focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="free" {{ old('plan_tier', $user->plan_tier ?? 'free') === 'free' ? 'selected' : '' }}>Free Tier</option>
                            <option value="starter" {{ old('plan_tier', $user->plan_tier) === 'starter' ? 'selected' : '' }}>Starter</option>
                            <option value="pro" {{ old('plan_tier', $user->plan_tier) === 'pro' ? 'selected' : '' }}>Pro Operator</option>
                            <option value="enterprise" {{ old('plan_tier', $user->plan_tier) === 'enterprise' ? 'selected' : '' }}>Enterprise Hub</option>
                        </select>
                        @error('plan_tier')
                            <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Storage Limit MB -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Storage Quota Limit (MB) <span class="text-rose-400">*</span>
                        </label>
                        <input type="number" name="storage_limit_mb" value="{{ old('storage_limit_mb', $user->storage_limit_mb ?? 100) }}" min="10" max="1048576" required class="w-full text-xs sm:text-sm bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 font-mono focus:ring-indigo-500 focus:border-indigo-500">
                        @error('storage_limit_mb')
                            <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email Verification Toggle -->
                    <div class="flex items-center pt-6">
                        <label class="relative flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="email_verified" value="1" {{ $user->email_verified_at ? 'checked' : '' }} class="mt-0.5 rounded bg-slate-900 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-xs font-bold text-white">Email Address Verified</span>
                                <p class="text-[11px] text-slate-400">If checked, the account bypasses email verification screens.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <a href="{{ route('admin.users.show', $user) }}" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition flex items-center gap-1.5">
                        <span>💾</span> Save Profile Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Form 2: Admin Password Reset -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
            <div class="border-b border-slate-800 pb-4">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <span>🔑</span> Admin Password Reset
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Directly set a new password for this user if they are locked out or need credentials rotated.</p>
            </div>

            <form method="POST" action="{{ route('admin.users.update-password', $user) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- New Password -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            New Password <span class="text-rose-400">*</span>
                        </label>
                        <input type="password" name="password" required minlength="8" placeholder="At least 8 characters" class="w-full text-xs sm:text-sm bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500 font-mono">
                        @error('password')
                            <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Confirm New Password <span class="text-rose-400">*</span>
                        </label>
                        <input type="password" name="password_confirmation" required minlength="8" placeholder="Repeat new password" class="w-full text-xs sm:text-sm bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500 font-mono">
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
                    <button type="submit" onclick="return confirm('Change password for {{ $user->name }}?');" class="px-6 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-md shadow-amber-600/30 transition flex items-center gap-1.5">
                        <span>⚡</span> Reset User Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
