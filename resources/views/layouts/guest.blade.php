<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    @include('layouts.guest.partials.head')
    <body class="font-sans text-slate-100 antialiased min-h-screen flex flex-col justify-between selection:bg-brand-500 selection:text-white relative overflow-x-hidden">
        @include('layouts.guest.partials.ambient')
        @include('layouts.guest.partials.header')

        <!-- Main Content Slot -->
        <main class="w-full flex-1 flex flex-col justify-center items-center px-4 py-8 relative z-10">
            {{ $slot }}
        </main>

        @include('layouts.guest.partials.footer')
        @stack('scripts')
    </body>
</html>
