<!-- Operator Identity Badge in Sidebar -->
<div class="p-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/40">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 to-cyan-400 text-white font-black text-sm flex items-center justify-center font-mono shadow-sm shrink-0">
            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ Auth::user()->name }}</div>
            <div class="text-[10px] font-mono text-slate-400 truncate">{{ Auth::user()->email }}</div>
            <div class="mt-1">
                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ Auth::user()->hasRole('admin') ? 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-300 border border-indigo-500/30' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' }}">
                    {{ Auth::user()->hasRole('admin') ? '👑 Administrator' : '⚡ Operator' }}
                </span>
            </div>
        </div>
    </div>
</div>
