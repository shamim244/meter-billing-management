@include('layouts.admin.partials.head')
<body x-data="{ sidebarOpen: false }" class="font-sans antialiased bg-slate-900 text-slate-100 min-h-screen flex flex-col md:flex-row">

    @include('layouts.admin.partials.backdrop')

    @include('layouts.admin.partials.sidebar')

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen overflow-x-hidden">
        <x-impersonation-banner />
        @include('layouts.admin.partials.header')
        @include('layouts.admin.partials.alerts')

        <!-- Page Body -->
        <main class="flex-1 p-4 sm:p-8">
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
