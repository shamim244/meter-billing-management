<!-- Mode Switcher Bottom Actions -->
<div class="p-4 border-t border-slate-200 dark:border-slate-800 space-y-2 bg-slate-50/50 dark:bg-slate-950/40">
    <!-- Switch to Dashboard -->
    <a href="{{ route('dashboard') }}" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-brand-600 to-cyan-600 hover:from-brand-500 hover:to-cyan-500 shadow-md shadow-brand-500/20 transition group">
        <span>📊 App Dashboard</span>
        <span class="group-hover:translate-x-0.5 transition">→</span>
    </a>

    @if(Auth::user()->hasRole('admin'))
        <!-- Switch to Admin Panel -->
        <a href="{{ route('admin.dashboard') }}" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200/60 dark:border-indigo-800/60 transition">
            <span>👑 SaaS Admin Panel</span>
        </a>
    @endif

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
            <span>🚪 Log Out</span>
        </button>
    </form>
</div>
