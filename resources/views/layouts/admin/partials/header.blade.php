<!-- Top Nav Header with Mode Switchers -->
<header class="h-16 bg-slate-950/80 backdrop-blur-md border-b border-slate-800 px-4 sm:px-8 flex items-center justify-between sticky top-0 z-20">
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen" class="p-2 -ml-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-900 md:hidden transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <h2 class="text-base sm:text-lg font-bold text-white truncate">
            {{ $header ?? 'Administration' }}
        </h2>
    </div>

    <div class="flex items-center gap-2 sm:gap-3">
        <!-- Fast Switch to Dashboard -->
        <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-cyan-300 bg-cyan-950/60 hover:bg-cyan-900/60 border border-cyan-500/30 transition shadow-xs">
            <span>📊 Dashboard</span>
        </a>

        <!-- Fast Switch to User Panel -->
        <a href="{{ route('user-panel.index') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-300 bg-slate-900 hover:bg-slate-800 border border-slate-800 transition shadow-xs">
            <span>👤 User Panel</span>
        </a>

        <div class="flex items-center gap-2 pl-2 border-l border-slate-800">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/15 text-indigo-400 border border-indigo-500/30">
                👑 Admin
            </span>
            <span class="text-xs font-bold text-slate-200 hidden md:inline">{{ Auth::user()->name }}</span>
        </div>
    </div>
</header>
