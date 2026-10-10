<!-- Section 5: System Config & Reports -->
<div class="space-y-1">
    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-500 tracking-wider">
        System Config & Logs
    </div>

    <a href="{{ route('admin.shortcuts.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.shortcuts.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">⌨️</span>
        <span>Shortcut Defaults</span>
    </a>

    <a href="{{ route('admin.api_hub.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ (request()->routeIs('admin.api_hub.*') || request()->routeIs('admin.rate_limits.*')) ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">⚡</span>
        <span>API & Automation Hub</span>
    </a>

    <a href="{{ route('admin.tags.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.tags.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">🏷️</span>
        <span>Review Tags</span>
    </a>

    <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.reports.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">📊</span>
        <span>Usage & Health Reports</span>
    </a>

    <a href="{{ url('/pulse') }}" target="_blank" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition text-slate-400 hover:text-white hover:bg-slate-900 group">
        <div class="flex items-center gap-3">
            <span class="text-base text-rose-500 animate-pulse">💓</span>
            <span>Pulse Monitor</span>
        </div>
        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20">LIVE</span>
    </a>

    <!-- Notification Engine Dropdown -->
    <div x-data="{ notifNav: {{ request()->routeIs('admin.notifications.*') ? 'true' : 'false' }} }" class="space-y-1">
        <button type="button" @click="notifNav = !notifNav" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.notifications.*') ? 'bg-slate-900 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
            <div class="flex items-center gap-3">
                <span class="text-base">🔔</span>
                <span>Notifications Hub</span>
            </div>
            <svg class="w-4 h-4 transition-transform text-slate-500" :class="notifNav ? 'rotate-180 text-indigo-400' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="notifNav" x-cloak class="pl-6 pr-1 py-1 space-y-1 border-l-2 border-slate-800 ml-5">
            <a href="{{ route('admin.notifications.email_providers.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.notifications.email_providers.*') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                <span>⚡</span> Email Providers
            </a>
            <a href="{{ route('admin.notifications.mailbox.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.notifications.mailbox.*') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                <span>📫</span> Live Mailbox
            </a>
            <a href="{{ route('admin.notifications.templates.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.notifications.templates.*') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                <span>📑</span> Templates
            </a>
            <a href="{{ route('admin.notifications.failed_queue') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.notifications.failed_queue') ? 'text-rose-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                <span>🚨</span> Failed Queue
            </a>
        </div>
    </div>

    <a href="{{ route('admin.issues.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.issues.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <div class="flex items-center gap-3">
            <span class="text-base">🐞</span>
            <span>Bug Tracker & AI Desk</span>
        </div>
        @php
            $pendingIssuesCount = \App\Models\IssueReport::where('status', 'pending')->count();
        @endphp
        @if($pendingIssuesCount > 0)
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-slate-950 font-mono">{{ $pendingIssuesCount }}</span>
        @endif
    </a>

    <a href="{{ route('admin.backups.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.backups.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">💾</span>
        <span>Disaster Recovery & Backups</span>
    </a>

    <a href="{{ route('admin.server_migration.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.server_migration.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">🚀</span>
        <span>Cloud Migration & Portability</span>
    </a>
</div>
