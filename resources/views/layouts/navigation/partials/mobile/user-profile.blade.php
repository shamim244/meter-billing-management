<!-- Mobile User Profile Block -->
<div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2">
    <div class="flex items-center justify-between px-2 py-1">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-cyan-400 text-white font-black text-xs flex items-center justify-center font-mono shadow-sm">
                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
            </div>
            <div>
                <div class="font-bold text-sm text-slate-900 dark:text-white">{{ Auth::user()->name }}</div>
                <div class="text-xs font-mono text-slate-400 truncate max-w-[200px]">{{ Auth::user()->email }}</div>
            </div>
        </div>
        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded {{ Auth::user()->hasRole('admin') ? 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-300' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' }}">
            {{ Auth::user()->hasRole('admin') ? 'Admin' : 'Operator' }}
        </span>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="pt-1">
        @csrf
        <x-responsive-nav-link :href="route('logout')"
                onclick="event.preventDefault(); this.closest('form').submit();"
                class="text-rose-600 dark:text-rose-400 font-bold hover:bg-rose-50 dark:hover:bg-rose-950/40">
            <span>🚪</span>
            <span>Log Out</span>
        </x-responsive-nav-link>
    </form>
</div>
