@php
    $isAdminActive = request()->routeIs('admin.*');
    $topPendingPayments = \App\Models\Payment::withoutUserScope()->where('status', \App\Enums\PaymentStatus::PENDING_VERIFICATION->value)->count();
    $topPendingIssues = \App\Models\IssueReport::where('status', 'pending')->count();
@endphp

{{-- Admin Suite Direct Mega Dropdown Cluster --}}
<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" 
            @click="open = !open" 
            :aria-expanded="open.toString()"
            aria-haspopup="true"
            class="inline-flex items-center gap-1.5 whitespace-nowrap px-2.5 lg:px-3 py-1.5 rounded-xl text-xs lg:text-sm font-bold transition-all duration-150 cursor-pointer {{ $isAdminActive ? 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30 shadow-xs' : 'text-indigo-700 dark:text-indigo-300 bg-indigo-50/70 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200/80 dark:border-indigo-800/60' }}">
        <span>👑</span>
        <span class="whitespace-nowrap">Admin Suite</span>
        @if($topPendingPayments > 0 || $topPendingIssues > 0)
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse" title="Pending Action Items"></span>
        @endif
        <svg class="w-3.5 h-3.5 text-indigo-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    {{-- Mega Dropdown Panel --}}
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
         x-cloak
         class="absolute left-0 lg:-left-36 xl:-left-20 mt-2 w-[calc(100vw-2rem)] sm:w-[600px] lg:w-[680px] max-w-[calc(100vw-2rem)] bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-indigo-100 dark:border-slate-800 z-50 overflow-hidden"
         @click="open = false">
        
        <div class="px-4 py-2.5 bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-transparent border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm">👑</span>
                <span class="text-xs font-black uppercase text-slate-800 dark:text-white tracking-wider">SaaS Administration Control</span>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                Open Admin Dashboard →
            </a>
        </div>

        <div class="p-3 grid grid-cols-3 gap-3 text-xs max-h-[calc(100vh-8rem)] overflow-y-auto custom-scrollbar">
            {{-- Col 1: Operations --}}
            <div class="space-y-0.5">
                <div class="px-2 py-1 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">⚡ Operations</div>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>📊</span><span>Dashboard</span></a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>👥</span><span>Billing Agents</span></a>
                <a href="{{ route('admin.mrus.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>🗂️</span><span>MRU Master</span></a>
                <a href="{{ route('admin.bills.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>📑</span><span>Bills Inspector</span></a>
                <a href="{{ route('admin.bills.engine-settings') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>⚡</span><span>Engine Settings</span></a>
                <a href="{{ route('admin.compression.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>🗜️</span><span>Compression</span></a>
            </div>

            {{-- Col 2: Finance & Plans --}}
            <div class="space-y-0.5">
                <div class="px-2 py-1 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">💳 Finance & Plans</div>
                <a href="{{ route('admin.wallets.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>👛</span><span>Agent Wallets</span></a>
                <a href="{{ route('admin.payments.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>💳</span><span>Transactions</span></a>
                <a href="{{ route('admin.payments.manual') }}" class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span class="flex items-center gap-2"><span>⏳</span><span>Manual Approvals</span></span>
                    @if($topPendingPayments > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black bg-amber-500/20 text-amber-500 font-mono">{{ $topPendingPayments }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.plans.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>📋</span><span>Subscription Plans</span></a>
                <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>🔄</span><span>Subscriptions</span></a>
                <a href="{{ route('admin.coupons.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>🎟️</span><span>Coupons</span></a>
                <a href="{{ route('admin.referrals.settings') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>🎁</span><span>Referral Settings</span></a>
            </div>

            {{-- Col 3: Platform & DevOps --}}
            <div class="space-y-0.5">
                <div class="px-2 py-1 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">🛡️ Platform & DevOps</div>
                <a href="{{ route('admin.notifications.email_providers.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>🔔</span><span>Notifications Hub</span></a>
                <a href="{{ route('admin.issues.index') }}" class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span class="flex items-center gap-2"><span>🐞</span><span>Bug Tracker</span></span>
                    @if($topPendingIssues > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black bg-amber-500/20 text-amber-500 font-mono">{{ $topPendingIssues }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.shortcuts.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>⌨️</span><span>Shortcut Defaults</span></a>
                <a href="{{ route('admin.api_hub.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>⚡</span><span>API Hub</span></a>
                <a href="{{ route('admin.tags.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>🏷️</span><span>Review Tags</span></a>
                <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>📊</span><span>System Reports</span></a>
                <a href="{{ route('admin.backups.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>💾</span><span>Disaster Recovery</span></a>
                <a href="{{ route('admin.server_migration.index') }}" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300"><span>🚀</span><span>Cloud Migration</span></a>
            </div>
        </div>

        <div class="px-4 py-2 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-between text-[11px]">
            <a href="{{ url('/pulse') }}" target="_blank" class="flex items-center gap-1.5 text-rose-500 font-bold hover:underline">
                <span class="animate-pulse">💓</span><span>Live Pulse Monitor</span>
            </a>
            <span class="text-slate-400">2-Click Quick Navigation</span>
        </div>
    </div>
</div>
