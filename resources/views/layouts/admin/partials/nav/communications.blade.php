@php
    $isCommActive = request()->routeIs('admin.notifications.*');
@endphp

<!-- Pillar 4: Communications Hub -->
<div x-data="{ open: {{ $isCommActive ? 'true' : 'false' }} }" class="space-y-0.5">
    <button type="button" 
            @click="open = !open" 
            class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition text-xs font-bold {{ $isCommActive ? 'bg-slate-900 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-900/50' }}">
        <div class="flex items-center gap-2.5">
            <span class="text-sm">🔔</span>
            <span class="tracking-wide">Communications Hub</span>
        </div>
        <svg class="w-3.5 h-3.5 transition-transform duration-200 text-slate-500" 
             :class="open ? 'rotate-180 text-indigo-400' : ''" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-cloak class="pl-2.5 ml-3.5 border-l border-slate-800 space-y-0.5 py-1">
        <a href="{{ route('admin.notifications.email_providers.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.notifications.email_providers.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>⚡</span>
            <span>Email Providers</span>
        </a>

        <a href="{{ route('admin.notifications.mailbox.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.notifications.mailbox.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>📫</span>
            <span>Live Mailbox</span>
        </a>

        <a href="{{ route('admin.notifications.templates.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.notifications.templates.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>📑</span>
            <span>Notification Templates</span>
        </a>

        <a href="{{ route('admin.notifications.failed_queue') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.notifications.failed_queue') ? 'bg-rose-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>🚨</span>
            <span>Failed Queue</span>
        </a>
    </div>
</div>
