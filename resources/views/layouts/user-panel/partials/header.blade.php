<!-- Top Nav Header with Mode Switcher & Theme Toggle -->
<header class="h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 px-4 sm:px-8 flex items-center justify-between sticky top-0 z-30">
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen" class="p-2 -ml-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 md:hidden transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div>
            <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white truncate tracking-tight">
                {{ $header ?? 'User Control Center' }}
            </h2>
        </div>
    </div>

    <div class="flex items-center gap-2 sm:gap-3">
        <!-- Theme Toggle Button -->
        <button @click="toggleTheme()" 
                type="button" 
                class="w-9 h-9 rounded-xl flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition shadow-xs border border-slate-200/60 dark:border-slate-700/60" 
                :title="darkMode ? 'Switch to Light Theme' : 'Switch to Dark Theme'">
            <span x-show="!darkMode">🌙</span>
            <span x-show="darkMode" x-cloak>☀️</span>
        </button>

        <!-- Top Bar Quick Switch to Dashboard -->
        <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/70 dark:hover:bg-brand-900/70 text-brand-700 dark:text-cyan-300 font-bold text-xs border border-brand-200/60 dark:border-brand-800/60 transition shadow-xs">
            <span>📊 Dashboard</span>
        </a>
    </div>
</header>
