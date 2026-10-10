@include('layouts.user-panel.partials.head')
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

    @include('layouts.user-panel.partials.backdrop')

    @include('layouts.user-panel.partials.sidebar')

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen overflow-x-hidden">
        <x-impersonation-banner />
        @include('layouts.user-panel.partials.header')
        @include('layouts.user-panel.partials.alerts')

        <!-- Page Body -->
        <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto">
            @if(isset($slot))
                {{ $slot }}
            @else
                @yield('content')
            @endif
        </main>
    </div>

    @stack('scripts')
</body>
</html>
