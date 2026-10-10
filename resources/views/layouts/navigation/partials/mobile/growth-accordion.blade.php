@php
    $isGrowthSection = request()->routeIs('reports.*', 'referrals.*', 'wallet.*', 'user-panel.*');
@endphp

<!-- Mobile Growth & Account Accordion -->
<div class="rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 overflow-hidden">
    <button type="button" 
            @click="activeSection = activeSection === 'growth' ? '' : 'growth'" 
            :aria-expanded="activeSection === 'growth' ? 'true' : 'false'"
            class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs font-bold transition text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60">
        <div class="flex items-center gap-2">
            <span>📈</span>
            <span>Growth & Account Hub</span>
        </div>
        <svg class="w-4 h-4 transition-transform duration-200 text-slate-400" :class="activeSection === 'growth' ? 'rotate-180 text-brand-600 dark:text-cyan-400' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="activeSection === 'growth'" x-collapse x-cloak class="px-2 pb-2 space-y-1 border-t border-slate-200/60 dark:border-slate-800/60 pt-1">
        <x-responsive-nav-link :href="route('reports.usage')" :active="request()->routeIs('reports.*')">
            <span>📈</span>
            <span class="font-bold">Usage & Reports</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('referrals.index')" :active="request()->routeIs('referrals.*')">
            <span>🎁</span>
            <span class="font-bold">Refer & Earn Program</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('wallet.index')" :active="request()->routeIs('wallet.*')">
            <span>👛</span>
            <span class="font-bold">Wallet Ledger</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('user-panel.index')" :active="request()->routeIs('user-panel.index')">
            <span>👤</span>
            <span class="font-bold">User Control Panel</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('user-panel.subscription')">
            <span>💳</span>
            <span>Subscription & Quotas</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('user-panel.shortcuts')">
            <span>⌨️</span>
            <span>Keyboard Shortcuts</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('user-panel.preferences')">
            <span>⚙️</span>
            <span>General Preferences</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('user-panel.profile')">
            <span>👤</span>
            <span>Profile & Security</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('user-panel.api-keys')">
            <span>🔑</span>
            <span>API Keys & Integrations</span>
        </x-responsive-nav-link>
    </div>
</div>
