<nav x-data="{ open: false }" class="sticky top-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 transition-colors">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            
            @include('layouts.navigation.partials.brand-and-desktop-links')

            <!-- Right Side Controls: Status, Theme Toggle & User Menu -->
            <div class="hidden sm:flex sm:items-center gap-3">
                <!-- User Control Panel Fast Switch Button -->
                <a href="{{ route('user-panel.index') }}" class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition">
                    <span>👤</span>
                    <span>User Panel</span>
                </a>

                @if(Auth::user()->hasRole('admin'))
                    <!-- Admin Panel Fast Switch Button -->
                    <a href="{{ route('admin.dashboard') }}" class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800/80 transition">
                        <span>👑</span>
                        <span>Admin</span>
                    </a>
                @endif

                @include('layouts.navigation.partials.notification-dropdown')
                @include('layouts.navigation.partials.theme-toggle')
                @include('layouts.navigation.partials.user-dropdown')
            </div>

            <!-- Mobile Controls: Theme Toggle & Hamburger -->
            <div class="flex items-center gap-2 sm:hidden">
                @include('layouts.navigation.partials.theme-toggle')

                <!-- Hamburger Button -->
                <button @click="open = ! open" class="p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" aria-label="Toggle Navigation">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    @include('layouts.navigation.partials.mobile-drawer')
</nav>

<!-- Decoupled Navigation Alpine Logic -->
<script src="{{ asset('js/navigation/navigation-app.js') }}?v={{ file_exists(public_path('js/navigation/navigation-app.js')) ? filemtime(public_path('js/navigation/navigation-app.js')) : time() }}"></script>
