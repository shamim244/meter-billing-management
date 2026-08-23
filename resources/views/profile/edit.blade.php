<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 to-cyan-400 p-0.5 shadow-lg shadow-brand-500/20 flex items-center justify-center">
                    <span class="text-xl">⚙️</span>
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Account & Operator Profile</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Manage your identity, security credentials & billing preferences</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
                    <span>📊</span>
                    <span>Go to Dashboard</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Profile Identity Hero Card -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/5 dark:bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl bg-gradient-to-tr from-brand-600 to-cyan-400 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-brand-500/20 font-mono">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                    {{ $user->name }}
                                </h2>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $user->hasRole('admin') ? 'bg-indigo-100 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/80' : 'bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80' }}">
                                    {{ $user->hasRole('admin') ? '🛡️ Administrator' : '👤 Operator' }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    Status: <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase">{{ $user->status ?? 'active' }}</span>
                                </span>
                            </div>
                            <p class="text-xs font-mono text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2">
                                <span>✉️ {{ $user->email }}</span>
                                @if(!empty($user->phone))
                                    <span>•</span>
                                    <span>📱 {{ $user->phone }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Live Stats Strip -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="bg-slate-50 dark:bg-slate-950/60 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 text-center">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">MRUs</div>
                            <div class="text-lg font-black text-brand-600 dark:text-brand-400 font-mono mt-0.5">{{ $stats['mru_count'] }}</div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-950/60 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 text-center">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Consumers</div>
                            <div class="text-lg font-black text-cyan-600 dark:text-cyan-400 font-mono mt-0.5">{{ $stats['consumer_count'] }}</div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-950/60 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 text-center">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bills Logged</div>
                            <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">{{ $stats['bills_count'] }}</div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-950/60 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 text-center">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Joined</div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 font-mono mt-1.5">{{ $stats['created_at'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-Column Configuration Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Section 1: Profile Information -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <!-- Section 2: Update Password -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl">
                    @include('profile.partials.update-password-form')
                </div>

            </div>

            <!-- Section 3: Active Keyboard Shortcuts Overview -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-cyan-400 flex items-center justify-center text-lg font-black">
                            ⌨️
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Active Keyboard Shortcuts</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Your personalized hotkeys for rapid card reviewing and ledger operations</p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard') }}" class="px-3 py-1.5 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/60 dark:hover:bg-brand-900/60 text-brand-600 dark:text-cyan-300 rounded-xl text-xs font-bold transition flex items-center gap-1">
                        <span>⚙️ Configure on Dashboard</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($shortcuts as $key => $binding)
                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950/50 border border-slate-200/70 dark:border-slate-800/80 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate pr-2">
                                {{ $shortcutLabels[$key] ?? ucwords(str_replace('_', ' ', $key)) }}
                            </span>
                            <span class="px-2 py-1 rounded-lg bg-slate-200 dark:bg-slate-800 font-mono font-black text-xs text-brand-600 dark:text-cyan-300 border border-slate-300/80 dark:border-slate-700 shadow-sm shrink-0">
                                {{ strtoupper($binding) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Section 4: Danger Zone -->
            <div class="bg-rose-50/50 dark:bg-rose-950/20 rounded-3xl p-6 sm:p-8 border border-rose-200/80 dark:border-rose-900/40 shadow-xl">
                @include('profile.partials.delete-user-form')
            </div>

        </div>
    </div>
</x-app-layout>
