<!-- Responsive Mobile Drawer -->
<div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 px-4 pt-3 pb-5 space-y-3">
    <!-- Working Mode Operational Links -->
    <div class="space-y-1">
        <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">
            ⚡ Working Operations
        </div>

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

        <x-responsive-nav-link :href="route('reports.usage')" :active="request()->routeIs('reports.*')">
            <span>📈</span>
            <span class="font-bold">Usage & Reports</span>
        </x-responsive-nav-link>
    </div>

    <!-- Mode Switchers & Account Links -->
    <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-1">
        <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">
            👤 Control & Account Hub
        </div>

        <x-responsive-nav-link :href="route('user-panel.index')" :active="request()->routeIs('user-panel.index')">
            <span>👤</span>
            <span class="font-bold">User Control Panel</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('referrals.index')" :active="request()->routeIs('referrals.*')">
            <span>🎁</span>
            <span class="font-bold">Refer & Earn Program</span>
        </x-responsive-nav-link>

        <x-responsive-nav-link :href="route('wallet.index')" :active="request()->routeIs('wallet.*')">
            <span>👛</span>
            <span class="font-bold">Wallet Ledger</span>
        </x-responsive-nav-link>

        @if(Auth::user()->hasRole('admin'))
            <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')" class="text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/30">
                <span>👑</span>
                <span class="font-bold">SaaS Admin Panel</span>
            </x-responsive-nav-link>
        @endif
    </div>

    <!-- Mobile User Profile Block -->
    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2">
        <div class="flex items-center justify-between px-2 py-1">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-cyan-400 text-white font-black text-xs flex items-center justify-center font-mono shadow-sm">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                <div>
                    <div class="font-bold text-sm text-slate-900 dark:text-white">{{ Auth::user()->name }}</div>
                    <div class="text-xs font-mono text-slate-400 truncate max-w-[200px]">{{ Auth::user()->email }}</div>
                </div>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded {{ Auth::user()->hasRole('admin') ? 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-300' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' }}">
                {{ Auth::user()->hasRole('admin') ? 'Admin' : 'Operator' }}
            </span>
        </div>

        <div class="space-y-1 pt-1">
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

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="text-rose-600 dark:text-rose-400">
                    <span>🚪</span>
                    <span>Log Out</span>
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</div>
