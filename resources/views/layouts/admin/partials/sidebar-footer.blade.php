<!-- Mode Switcher Bottom Actions -->
<div class="p-4 border-t border-slate-800/80 space-y-2 bg-slate-950/60">
    <!-- Switch to Dashboard -->
    <a href="{{ route('dashboard') }}" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-cyan-400 hover:from-emerald-300 hover:to-cyan-300 shadow-md shadow-cyan-500/20 transition group">
        <span>📊 App Dashboard</span>
        <span class="group-hover:translate-x-0.5 transition">→</span>
    </a>

    <!-- Switch to User Control Panel -->
    <a href="{{ route('user-panel.index') }}" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-slate-300 bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 transition">
        <span>👤 User Control Panel</span>
    </a>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-rose-400 hover:bg-rose-950/30 transition">
            <span>🚪 Log Out</span>
        </button>
    </form>
</div>
