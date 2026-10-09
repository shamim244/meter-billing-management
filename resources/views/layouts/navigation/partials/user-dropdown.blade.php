<!-- User Dropdown Menu -->
<x-dropdown align="right" width="64">
    <x-slot name="trigger">
        <button class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800/80 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 font-semibold text-xs transition shadow-sm">
            <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-brand-600 to-cyan-400 text-white font-black text-[10px] flex items-center justify-center font-mono shadow-sm">
                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
            </div>
            <span class="truncate max-w-[120px]">{{ Auth::user()->name }}</span>
            @if(Auth::user()->hasRole('admin'))
                <span class="px-1.5 py-0.2 text-[9px] font-black uppercase rounded bg-indigo-500/15 text-indigo-600 dark:text-indigo-300 border border-indigo-500/30">Admin</span>
            @endif
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
    </x-slot>

    <x-slot name="content">
        <!-- User Card Info -->
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
            <div class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ Auth::user()->name }}</div>
            <div class="text-[11px] font-mono text-slate-400 truncate">{{ Auth::user()->email }}</div>
            <div class="mt-2 flex items-center justify-between">
                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ Auth::user()->hasRole('admin') ? 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-300 border border-indigo-500/30' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' }}">
                    {{ Auth::user()->hasRole('admin') ? '👑 Administrator' : '⚡ Operator' }}
                </span>
                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-brand-500/10 text-brand-600 dark:text-cyan-400 border border-brand-500/20">
                    {{ strtoupper(Auth::user()->current_plan_name ?? Auth::user()->plan_tier ?? 'Free') }}
                </span>
            </div>
        </div>

        <!-- Quick Wallet Balance Pill -->
        <div class="p-2.5 bg-slate-50/80 dark:bg-slate-950/60 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <span class="text-[10px] text-slate-400 font-bold block">WALLET BALANCE</span>
                <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 font-mono">₹{{ number_format(Auth::user()->balanceFloat, 2) }}</span>
            </div>
            <a href="{{ route('payments.create') }}" class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition">
                + Top Up
            </a>
        </div>

        <div class="py-1">
            <x-dropdown-link :href="route('user-panel.index')" class="font-semibold">
                <span>👤</span>
                <span>User Control Panel</span>
            </x-dropdown-link>

            <x-dropdown-link :href="route('referrals.index')">
                <span>🎁</span>
                <span>Refer & Earn Program</span>
            </x-dropdown-link>

            <x-dropdown-link :href="route('user-panel.subscription')">
                <span>💳</span>
                <span>Subscription & Quotas</span>
            </x-dropdown-link>

            <x-dropdown-link :href="route('wallet.index')">
                <span>👛</span>
                <span>Wallet & Ledger</span>
            </x-dropdown-link>

            <x-dropdown-link :href="route('user-panel.shortcuts')">
                <span>⌨️</span>
                <span>Keyboard Shortcuts</span>
            </x-dropdown-link>

            <x-dropdown-link :href="route('user-panel.preferences')">
                <span>⚙️</span>
                <span>General Preferences</span>
            </x-dropdown-link>

            <x-dropdown-link :href="route('user-panel.api-keys')">
                <span>🔑</span>
                <span>API Keys & Integrations</span>
            </x-dropdown-link>

            @if(Auth::user()->hasRole('admin'))
                <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                <x-dropdown-link :href="route('admin.dashboard')" class="text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/30">
                    <span>👑</span>
                    <span>SaaS Admin Panel →</span>
                </x-dropdown-link>
            @endif
        </div>

        <!-- Log Out -->
        <div class="pt-1 border-t border-slate-100 dark:border-slate-800">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                    <span>🚪</span>
                    <span>Log Out</span>
                </x-dropdown-link>
            </form>
        </div>
    </x-slot>
</x-dropdown>
