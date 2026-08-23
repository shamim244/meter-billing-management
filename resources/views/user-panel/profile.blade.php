<x-user-panel-layout>
    <x-slot name="header">
        Profile & Security
    </x-slot>

    <div class="space-y-8">
        <!-- Profile Identity Hero -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl bg-gradient-to-tr from-brand-600 to-cyan-400 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-brand-500/20 font-mono">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                {{ $user->name }}
                            </h1>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $user->hasRole('admin') ? 'bg-indigo-100 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/80' : 'bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80' }}">
                                {{ $user->hasRole('admin') ? '👑 Administrator' : '⚡ Operator' }}
                            </span>
                        </div>
                        <p class="text-xs font-mono text-slate-500 dark:text-slate-400 mt-1">
                            Registered on {{ $user->created_at ? $user->created_at->format('F d, Y') : 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- 1. Profile Information Update -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl space-y-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Profile Information</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Update your operator account's name, email, and contact phone.</p>
                </div>

                <form method="POST" action="{{ route('user-panel.profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Full Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-brand-500 p-2.5">
                        @error('name')
                            <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Email Address</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-brand-500 p-2.5">
                        @error('email')
                            <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Mobile / WhatsApp (Optional)</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" placeholder="+91 98765 43210" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-brand-500 p-2.5">
                        @error('phone')
                            <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-md shadow-brand-500/20 transition">
                            💾 Save Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- 2. Security / Update Password -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl space-y-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Security & Password</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ensure your account is using a long, random password to stay secure.</p>
                </div>

                <form method="POST" action="{{ route('user-panel.password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Current Password</label>
                        <input id="current_password" name="current_password" type="password" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-brand-500 p-2.5">
                        @error('current_password', 'updatePassword')
                            <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">New Password</label>
                        <input id="password" name="password" type="password" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-brand-500 p-2.5">
                        @error('password', 'updatePassword')
                            <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Confirm New Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-brand-500 p-2.5">
                        @error('password_confirmation', 'updatePassword')
                            <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-md shadow-brand-500/20 transition">
                            🔒 Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-user-panel-layout>
