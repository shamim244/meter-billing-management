<!-- Quick Hub Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Card 1: Subscription & Storage Status -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 flex flex-col justify-between">
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Subscription & Quotas</span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-brand-50 dark:bg-brand-950/60 text-brand-600 dark:text-cyan-400">
                    {{ $user->current_plan_name ?? $user->plan_tier ?? 'Free Plan' }}
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
