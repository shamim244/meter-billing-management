@php
    $isSysActive = request()->routeIs('admin.shortcuts.*', 'admin.api_hub.*', 'admin.rate_limits.*', 'admin.tags.*', 'admin.reports.*', 'admin.issues.*', 'admin.backups.*', 'admin.server_migration.*');
    $pendingIssuesCount = \App\Models\IssueReport::where('status', 'pending')->count();
@endphp

<!-- Pillar 5: System & DevOps -->
<div x-data="{ open: {{ $isSysActive ? 'true' : 'false' }} }" class="space-y-0.5">
    <button type="button" 
            @click="open = !open" 
            class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition text-xs font-bold {{ $isSysActive ? 'bg-slate-900 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-900/50' }}">
        <div class="flex items-center gap-2.5">
            <span class="text-sm">🛡️</span>
            <span class="tracking-wide">System & DevOps</span>
        </div>
        <div class="flex items-center gap-1.5">
            @if($pendingIssuesCount > 0)
                <span class="px-1.5 py-0.2 text-[9px] font-black rounded-full bg-amber-500 text-slate-950 font-mono animate-pulse" title="{{ $pendingIssuesCount }} Pending Bug Reports">
                    {{ $pendingIssuesCount }}
                </span>
            @endif
            <svg class="w-3.5 h-3.5 transition-transform duration-200 text-slate-500" 
                 :class="open ? 'rotate-180 text-indigo-400' : ''" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </button>

    <div x-show="open" x-cloak class="pl-2.5 ml-3.5 border-l border-slate-800 space-y-0.5 py-1">
        <a href="{{ route('admin.issues.index') }}" 
           class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.issues.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <div class="flex items-center gap-2.5">
                <span>🐞</span>
                <span>Bug Tracker & AI Desk</span>
            </div>
            @if($pendingIssuesCount > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black {{ request()->routeIs('admin.issues.*') ? 'bg-white/20 text-white' : 'bg-amber-500/20 text-amber-400' }}">{{ $pendingIssuesCount }}</span>
            @endif
        </a>

        <a href="{{ route('admin.shortcuts.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.shortcuts.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>⌨️</span>
            <span>Shortcut Defaults</span>
        </a>

        <a href="{{ route('admin.api_hub.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ (request()->routeIs('admin.api_hub.*') || request()->routeIs('admin.rate_limits.*')) ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>⚡</span>
            <span>API & Automation Hub</span>
        </a>

        <a href="{{ route('admin.tags.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.tags.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>🏷️</span>
            <span>Review Tags</span>
        </a>

        <a href="{{ route('admin.reports.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.reports.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>📊</span>
            <span>Usage & Health Reports</span>
        </a>

        <a href="{{ url('/pulse') }}" target="_blank" 
           class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-[11px] transition text-slate-400 hover:text-white hover:bg-slate-900/60 group">
            <div class="flex items-center gap-2.5">
                <span class="text-rose-500 animate-pulse">💓</span>
                <span>Pulse Monitor</span>
            </div>
            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20">LIVE</span>
        </a>

        <a href="{{ route('admin.backups.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.backups.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>💾</span>
            <span>Disaster Recovery</span>
        </a>

        <a href="{{ route('admin.server_migration.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.server_migration.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>🚀</span>
            <span>Cloud Migration</span>
        </a>
    </div>
</div>
