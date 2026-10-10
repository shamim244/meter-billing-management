@php
    $isOpsActive = request()->routeIs('admin.dashboard', 'admin.users.*', 'admin.mrus.*', 'admin.bills.*', 'admin.compression.*');
@endphp

<!-- Pillar 1: Core Operations -->
<div x-data="{ open: {{ $isOpsActive ? 'true' : 'false' }} }" class="space-y-0.5">
    <button type="button" 
            @click="open = !open" 
            class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition text-xs font-bold {{ $isOpsActive ? 'bg-slate-900 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-900/50' }}">
        <div class="flex items-center gap-2.5">
            <span class="text-sm">⚡</span>
            <span class="tracking-wide">Core Operations</span>
        </div>
        <svg class="w-3.5 h-3.5 transition-transform duration-200 text-slate-500" 
             :class="open ? 'rotate-180 text-indigo-400' : ''" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-cloak class="pl-2.5 ml-3.5 border-l border-slate-800 space-y-0.5 py-1">
        <a href="{{ route('admin.dashboard') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>📊</span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('admin.users.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.users.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>👥</span>
            <span>Users & Billing Agents</span>
        </a>

        <a href="{{ route('admin.mrus.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.mrus.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>🗂️</span>
            <span>MRU Master List</span>
        </a>

        <a href="{{ route('admin.bills.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.bills.index') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>📑</span>
            <span>All Bills Inspector</span>
        </a>

        <a href="{{ route('admin.bills.engine-settings') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.bills.engine-settings*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>⚡</span>
            <span>Billing Engine Settings</span>
        </a>

        <a href="{{ route('admin.compression.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.compression.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>🗜️</span>
            <span>Adaptive Compression</span>
        </a>
    </div>
</div>
