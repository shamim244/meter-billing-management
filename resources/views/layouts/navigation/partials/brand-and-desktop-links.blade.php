{{-- Left Side: Brand Logo & Navigation Clusters --}}
<div class="flex items-center gap-4 lg:gap-6">
    {{-- Brand Logo --}}
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group shrink-0">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-cyan-400 p-0.5 shadow-md shadow-brand-500/20 group-hover:scale-105 transition duration-150">
            <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                <span class="text-base">⚡</span>
            </div>
        </div>
        <div>
            <div class="text-base font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-1.5 leading-none">
                <span>NBPDCL</span>
                <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-brand-500/15 text-brand-600 dark:text-brand-400 border border-brand-500/30">SaaS</span>
            </div>
        </div>
    </a>

    {{-- Desktop & Tablet Navigation Clusters (Direct & Grouped Dropdowns) --}}
    <div class="hidden lg:flex items-center gap-1 lg:gap-1.5">
        {{-- 1. Direct Dashboard Link --}}
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-xs lg:text-sm font-semibold px-2.5 lg:px-3 py-1.5">
            <span>📊</span>
            <span>Dashboard</span>
        </x-nav-link>

        {{-- 2. Operations Cluster Dropdown --}}
        @include('layouts.navigation.partials.nav.operations-menu')

        {{-- 3. Growth & Reports Cluster Dropdown --}}
        @include('layouts.navigation.partials.nav.growth-menu')

        {{-- 4. Dynamic Admin Suite Cluster Dropdown (Admins Only) --}}
        @if(Auth::user()->hasRole('admin'))
            @include('layouts.navigation.partials.nav.admin-suite-menu')
        @endif
    </div>
</div>
