<!-- Section 1: Account & Billing -->
<div class="space-y-1">
    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">
        Operator Account & Billing
    </div>

    <a href="{{ route('user-panel.index') }}" 
       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user-panel.index') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
        <span class="text-base">📊</span>
        <span>Overview & Stats</span>
    </a>

    <a href="{{ route('user-panel.subscription') }}" 
       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user-panel.subscription') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
        <span class="text-base">💳</span>
        <div class="flex-1 flex items-center justify-between">
            <span>Subscription & Quotas</span>
            <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-black {{ request()->routeIs('user-panel.subscription') ? 'bg-white/20 text-white' : 'bg-brand-100 dark:bg-brand-950 text-brand-700 dark:text-cyan-300' }}">
                {{ strtoupper(Auth::user()->current_plan_name ?? Auth::user()->plan_tier ?? 'Free') }}
            </span>
        </div>
    </a>

    <a href="{{ route('wallet.index') }}" 
       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('wallet.*') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
        <span class="text-base">👛</span>
        <div class="flex-1 flex items-center justify-between">
            <span>Wallet & Ledger</span>
            <span class="text-[10px] font-mono font-bold text-emerald-600 dark:text-emerald-400">
                ₹{{ number_format(Auth::user()->balanceFloat, 2) }}
            </span>
        </div>
    </a>
</div>
