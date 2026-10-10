@php
    $isAdminSection = request()->routeIs('admin.*');
    $mobPendingPayments = \App\Models\Payment::withoutUserScope()->where('status', \App\Enums\PaymentStatus::PENDING_VERIFICATION->value)->count();
    $mobPendingIssues = \App\Models\IssueReport::where('status', 'pending')->count();
@endphp

{{-- Mobile SaaS Administration Accordion --}}
<div class="rounded-xl border border-indigo-200/80 dark:border-indigo-900/60 bg-indigo-50/30 dark:bg-indigo-950/20 overflow-hidden">
    <button type="button" 
            @click="activeSection = activeSection === 'admin' ? '' : 'admin'" 
            :aria-expanded="activeSection === 'admin' ? 'true' : 'false'"
            class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-bold transition text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100/50 dark:hover:bg-indigo-900/40">
        <div class="flex items-center gap-2">
            <span>👑</span>
            <span>SaaS Administration</span>
        </div>
        <div class="flex items-center gap-1.5">
            @if($mobPendingPayments > 0 || $mobPendingIssues > 0)
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            @endif
            <svg class="w-4 h-4 transition-transform duration-200 text-indigo-400" :class="activeSection === 'admin' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
    </button>

    <div x-show="activeSection === 'admin'" x-collapse x-cloak class="px-2 pb-2 space-y-1 border-t border-indigo-200/60 dark:border-indigo-900/50 pt-1">
        <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')"><span>📊</span><span class="font-bold">Admin Dashboard</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')"><span>👥</span><span>Billing Agents</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.mrus.index')" :active="request()->routeIs('admin.mrus.*')"><span>🗂️</span><span>MRU Master</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.bills.index')" :active="request()->routeIs('admin.bills.index')"><span>📑</span><span>Bills Inspector</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.bills.engine-settings')" :active="request()->routeIs('admin.bills.engine-settings*')"><span>⚡</span><span>Billing Engine Settings</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.compression.index')" :active="request()->routeIs('admin.compression.*')"><span>🗜️</span><span>Adaptive Compression</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.wallets.index')" :active="request()->routeIs('admin.wallets.*')"><span>👛</span><span>Agent Wallets</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.payments.index')" :active="request()->routeIs('admin.payments.*')"><span>💳</span><span>Transactions & Gateways</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.payments.manual')" :active="request()->routeIs('admin.payments.manual')">
            <span>⏳</span>
            <span>Manual Approvals</span>
            @if($mobPendingPayments > 0)
                <span class="ml-auto px-1.5 py-0.2 rounded-full text-[9px] font-black bg-amber-500/20 text-amber-500 font-mono">{{ $mobPendingPayments }}</span>
            @endif
        </x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.plans.index')" :active="request()->routeIs('admin.plans.*')"><span>📋</span><span>Subscription Plans</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.subscriptions.index')" :active="request()->routeIs('admin.subscriptions.*')"><span>🔄</span><span>Agent Subscriptions</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.coupons.index')" :active="request()->routeIs('admin.coupons.*')"><span>🎟️</span><span>Coupon Campaigns</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.referrals.settings')" :active="request()->routeIs('admin.referrals.*')"><span>🎁</span><span>Referrals Admin</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.notifications.email_providers.index')" :active="request()->routeIs('admin.notifications.*')"><span>🔔</span><span>Notifications Hub</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.issues.index')" :active="request()->routeIs('admin.issues.*')">
            <span>🐞</span>
            <span>Bug Tracker & AI Desk</span>
            @if($mobPendingIssues > 0)
                <span class="ml-auto px-1.5 py-0.2 rounded-full text-[9px] font-black bg-amber-500/20 text-amber-500 font-mono">{{ $mobPendingIssues }}</span>
            @endif
        </x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.shortcuts.index')" :active="request()->routeIs('admin.shortcuts.*')"><span>⌨️</span><span>Shortcut Defaults</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.api_hub.index')" :active="request()->routeIs('admin.api_hub.*') || request()->routeIs('admin.rate_limits.*')"><span>⚡</span><span>API & Automation Hub</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.tags.index')" :active="request()->routeIs('admin.tags.*')"><span>🏷️</span><span>Review Tags</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')"><span>📊</span><span>System Reports</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="url('/pulse')" target="_blank"><span class="text-rose-500">💓</span><span>Pulse Monitor</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.backups.index')" :active="request()->routeIs('admin.backups.*')"><span>💾</span><span>Disaster Recovery</span></x-responsive-nav-link>
        <x-responsive-nav-link :href="route('admin.server_migration.index')" :active="request()->routeIs('admin.server_migration.*')"><span>🚀</span><span>Cloud Migration</span></x-responsive-nav-link>
    </div>
</div>
