<x-user-panel-layout>
    <x-slot name="header">
        Account Overview & Activity
    </x-slot>

    <div class="space-y-8">
        <!-- Identity & Status Hero Card -->
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
                            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                Plan: <span class="text-brand-600 dark:text-cyan-400 font-bold uppercase">{{ $user->plan_tier ?? 'Free' }}</span>
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

                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="px-5 py-2.5 bg-gradient-to-r from-brand-600 to-cyan-600 hover:from-brand-500 hover:to-cyan-500 text-white rounded-xl text-xs font-bold shadow-md shadow-brand-500/20 transition flex items-center gap-2">
                        <span>📊 Open Dashboard</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4 Core Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-300 flex items-center justify-center text-xl shrink-0">
                    🗂️
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">MRU Workspaces</div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ number_format($stats['mru_count']) }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Active areas</div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-300 flex items-center justify-center text-xl shrink-0">
                    👥
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Consumers</div>
                    <div class="text-2xl font-black text-cyan-600 dark:text-cyan-400 font-mono mt-0.5">{{ number_format($stats['consumer_count']) }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Master ledger CAs</div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-300 flex items-center justify-center text-xl shrink-0">
                    📄
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bills Processed</div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">{{ number_format($stats['bills_count']) }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($stats['pdf_count']) }} PDFs on disk</div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-300 flex items-center justify-center text-xl shrink-0">
                    💾
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Storage Used</div>
                    <div class="text-2xl font-black text-purple-600 dark:text-purple-400 font-mono mt-0.5">
                        {{ round($stats['storage_used_bytes'] / (1024 * 1024), 1) }} MB
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">
                        {{ $stats['storage_percent'] }}% of {{ round($stats['storage_limit_bytes'] / (1024 * 1024)) }} MB
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Hub Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card 1: Subscription & Storage Status -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Subscription & Quotas</span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-brand-50 dark:bg-brand-950/60 text-brand-600 dark:text-cyan-400">
                            {{ $user->plan_tier ?? 'Free Plan' }}
                        </span>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 dark:text-slate-400">PDF Disk Usage</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $stats['storage_percent'] }}%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-brand-500 to-cyan-400 rounded-full transition-all duration-300" style="width: {{ min(100, $stats['storage_percent']) }}%"></div>
                        </div>
                        <div class="text-[10px] text-slate-400 text-right">
                            {{ round($stats['storage_used_bytes'] / (1024 * 1024), 2) }} MB of {{ round($stats['storage_limit_bytes'] / (1024 * 1024)) }} MB
                        </div>
                    </div>
                </div>

                <a href="{{ route('user-panel.subscription') }}" class="w-full py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-1 transition">
                    <span>Manage Subscription</span>
                    <span>→</span>
                </a>
            </div>

            <!-- Card 2: Keyboard Shortcuts -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Review Shortcuts</span>
                        <span class="text-base">⌨️</span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Rapid single-key navigation for audit submissions, doubts, issues, and working reading entries.
                    </p>
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <span class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-mono text-[10px] font-bold text-slate-700 dark:text-slate-300">[Enter] OK</span>
                        <span class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-mono text-[10px] font-bold text-slate-700 dark:text-slate-300">[R] Reading</span>
                        <span class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-mono text-[10px] font-bold text-slate-700 dark:text-slate-300">[Esc] Exit</span>
                    </div>
                </div>

                <a href="{{ route('user-panel.shortcuts') }}" class="w-full py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-1 transition">
                    <span>Configure Keybindings</span>
                    <span>→</span>
                </a>
            </div>

            <!-- Card 3: Workspace Preferences -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Preferences</span>
                        <span class="text-base">⚙️</span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Customize your default review layout (Card vs Table), page sizes, audio feedback, and dark/light mode.
                    </p>
                </div>

                <a href="{{ route('user-panel.preferences') }}" class="w-full py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-1 transition">
                    <span>Adjust Preferences</span>
                    <span>→</span>
                </a>
            </div>
        </div>
    </div>
</x-user-panel-layout>
