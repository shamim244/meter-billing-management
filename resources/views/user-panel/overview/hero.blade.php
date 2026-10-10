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
                        Plan: <span class="text-brand-600 dark:text-cyan-400 font-bold uppercase">{{ $user->current_plan_name ?? $user->plan_tier ?? 'Free' }}</span>
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
