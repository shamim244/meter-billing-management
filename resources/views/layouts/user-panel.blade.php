<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ session('pref_theme') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'User Panel' }} — NBPDCL SaaS</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS & Alpine.js CDNs -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="/js/keyboard-shortcuts.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                        }
                    }
                }
            }
        }
    </script>
    <script>
        // Synchronize dark mode preference
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ 
    sidebarOpen: false,
    darkMode: document.documentElement.classList.contains('dark'),
    toggleTheme() {
        this.darkMode = !this.darkMode;
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        }
    }
}" class="font-sans antialiased bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen flex flex-col md:flex-row transition-colors duration-150">

    <!-- Mobile Drawer Backdrop -->
    <div x-show="sidebarOpen" 
         x-cloak 
         @click="sidebarOpen = false" 
         class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm md:hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    <!-- User Panel Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
           class="fixed md:sticky top-0 inset-y-0 left-0 z-50 w-64 sm:w-72 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between shrink-0 h-screen transition-transform duration-200 ease-in-out shadow-lg md:shadow-none overflow-y-auto">
        <div>
            <!-- Header Brand & Switcher Indicator -->
            <div class="h-16 px-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-cyan-500 flex items-center justify-center font-bold text-white shadow-md shadow-brand-500/20">
                        👤
                    </div>
                    <div>
                        <span class="font-extrabold text-sm tracking-tight text-slate-900 dark:text-white block leading-tight">User Control Center</span>
                        <span class="text-[10px] font-bold text-brand-600 dark:text-cyan-400 uppercase tracking-wider">Account Hub</span>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-slate-600 dark:hover:text-white p-1">
                    ✕
                </button>
            </div>

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

            <!-- User Panel Navigation Links -->
            <nav class="p-4 space-y-1.5 text-xs font-semibold">
                <a href="{{ route('user-panel.index') }}" 
                   class="flex items-center gap-3 px-3.5 py-3 rounded-xl transition {{ request()->routeIs('user-panel.index') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                    <span class="text-base">📊</span>
                    <span>Overview & Stats</span>
                </a>

                <a href="{{ route('user-panel.subscription') }}" 
                   class="flex items-center gap-3 px-3.5 py-3 rounded-xl transition {{ request()->routeIs('user-panel.subscription') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                    <span class="text-base">💳</span>
                    <div class="flex-1 flex items-center justify-between">
                        <span>Subscription & Quotas</span>
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-black {{ request()->routeIs('user-panel.subscription') ? 'bg-white/20 text-white' : 'bg-brand-100 dark:bg-brand-950 text-brand-700 dark:text-cyan-300' }}">
                            {{ strtoupper(Auth::user()->plan_tier ?? 'Free') }}
                        </span>
                    </div>
                </a>

                <a href="{{ route('user-panel.shortcuts') }}" 
                   class="flex items-center gap-3 px-3.5 py-3 rounded-xl transition {{ request()->routeIs('user-panel.shortcuts') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                    <span class="text-base">⌨️</span>
                    <span>Keyboard Shortcuts</span>
                </a>

                <a href="{{ route('user-panel.preferences') }}" 
                   class="flex items-center gap-3 px-3.5 py-3 rounded-xl transition {{ request()->routeIs('user-panel.preferences') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                    <span class="text-base">⚙️</span>
                    <span>General Preferences</span>
                </a>

                <a href="{{ route('user-panel.profile') }}" 
                   class="flex items-center gap-3 px-3.5 py-3 rounded-xl transition {{ request()->routeIs('user-panel.profile') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                    <span class="text-base">👤</span>
                    <span>Profile & Security</span>
                </a>
            </nav>
        </div>

        <!-- Mode Switcher Bottom Actions -->
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 space-y-2 bg-slate-50/50 dark:bg-slate-950/40">
            <!-- Switch to Working Mode -->
            <a href="{{ route('dashboard') }}" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-brand-600 to-cyan-600 hover:from-brand-500 hover:to-cyan-500 shadow-md shadow-brand-500/20 transition group">
                <span>⚡ Switch to Working Mode</span>
                <span class="group-hover:translate-x-0.5 transition">→</span>
            </a>

            @if(Auth::user()->hasRole('admin'))
                <!-- Switch to Admin Panel -->
                <a href="{{ route('admin.dashboard') }}" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200/60 dark:border-indigo-800/60 transition">
                    <span>👑 Admin Panel</span>
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                    <span>🚪 Log Out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen overflow-x-hidden">
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

                <!-- Top Bar Quick Switch to Working Mode -->
                <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/70 dark:hover:bg-brand-900/70 text-brand-700 dark:text-cyan-300 font-bold text-xs border border-brand-200/60 dark:border-brand-800/60 transition shadow-xs">
                    <span>⚡ Back to Working Mode</span>
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-4 sm:px-8 pt-4 sm:pt-6">
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm font-medium flex items-center gap-2 shadow-xs">
                    <span>✅</span> {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs sm:text-sm font-medium flex items-center gap-2 shadow-xs">
                    <span>❌</span> {{ session('error') }}
                </div>
            @endif
        </div>

        <!-- Page Body -->
        <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto">
            {{ $slot }}
        </main>
    </div>

</body>
</html>
