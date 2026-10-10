@php
    $isOpsSection = request()->routeIs('dashboard', 'field-desk.*', 'mrus.*', 'processing.*', 'pdf-manager.*');
@endphp

<!-- Mobile Operations Accordion -->
<div class="rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden">
    <button type="button" 
            @click="activeSection = activeSection === 'operations' ? '' : 'operations'" 
            :aria-expanded="activeSection === 'operations' ? 'true' : 'false'"
            class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-bold transition text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60">
        <div class="flex items-center gap-2">
            <span>⚡</span>
            <span>Working Operations</span>
        </div>
        <svg class="w-4 h-4 transition-transform duration-200 text-slate-400" :class="activeSection === 'operations' ? 'rotate-180 text-brand-600 dark:text-cyan-400' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="activeSection === 'operations'" x-collapse x-cloak class="px-2 pb-2 space-y-1 border-t border-slate-200/60 dark:border-slate-800/60 pt-1">
        <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            <span>📊</span>
            <span class="font-bold">Dashboard</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('field-desk.index')" :active="request()->routeIs('field-desk.*')">
            <span>📋</span>
            <span class="font-bold">FieldDesk</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('mrus.index')" :active="request()->routeIs('mrus.*')">
            <span>🗂️</span>
            <span class="font-bold">MRU Workspaces</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('processing.index')" :active="request()->routeIs('processing.*')">
            <span>⚡</span>
            <span class="font-bold">Data Processing</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('pdf-manager.index')" :active="request()->routeIs('pdf-manager.*')">
            <span>📑</span>
            <span class="font-bold">PDF Manager</span>
        </x-responsive-nav-link>
    </div>
</div>
