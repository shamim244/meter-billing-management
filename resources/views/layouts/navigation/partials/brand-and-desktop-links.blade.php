<!-- Left Side: Brand Logo & Navigation Links -->
<div class="flex items-center gap-6">
    <!-- Brand Logo -->
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
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

    <!-- Desktop Navigation Links (Working Mode Operational Focus) -->
    <div class="hidden sm:flex items-center gap-1.5">
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            <span>📊</span>
            <span>Dashboard</span>
        </x-nav-link>

        <x-nav-link :href="route('field-desk.index')" :active="request()->routeIs('field-desk.*')">
            <span>📋</span>
            <span>FieldDesk</span>
        </x-nav-link>

        <x-nav-link :href="route('mrus.index')" :active="request()->routeIs('mrus.*')">
            <span>🗂️</span>
            <span>MRUs</span>
        </x-nav-link>

        <x-nav-link :href="route('processing.index')" :active="request()->routeIs('processing.*')">
            <span>⚡</span>
            <span>Processing</span>
        </x-nav-link>

        <x-nav-link :href="route('pdf-manager.index')" :active="request()->routeIs('pdf-manager.*')">
            <span>📑</span>
            <span>PDF Manager</span>
        </x-nav-link>

        <x-nav-link :href="route('reports.usage')" :active="request()->routeIs('reports.*')">
            <span>📈</span>
            <span>Reports</span>
        </x-nav-link>
    </div>
</div>
