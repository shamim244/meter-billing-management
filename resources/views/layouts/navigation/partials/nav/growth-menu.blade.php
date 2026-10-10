@php
    $isGrowthActive = request()->routeIs('reports.*', 'referrals.*', 'wallet.*');
@endphp

<!-- Growth & Reports Dropdown Cluster -->
<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" 
            @click="open = !open" 
            :aria-expanded="open.toString()"
            aria-haspopup="true"
            class="inline-flex items-center gap-1.5 whitespace-nowrap px-2.5 lg:px-3 py-1.5 rounded-xl text-xs lg:text-sm font-semibold transition-all duration-150 cursor-pointer {{ $isGrowthActive ? 'bg-brand-500/10 dark:bg-brand-500/15 text-brand-600 dark:text-cyan-400 border border-brand-500/20 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-transparent' }}">
        <span>📈</span>
        <span class="whitespace-nowrap">Growth & Reports</span>
        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180 text-brand-600 dark:text-cyan-400' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <!-- Dropdown Panel -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
         x-cloak
         class="absolute left-0 mt-2 w-72 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/80 dark:border-slate-800 z-50 p-2 space-y-1"
         @click="open = false">
        
        <div class="px-3 py-1.5 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">
            Analytics & Financials
        </div>

        <a href="{{ route('reports.usage') }}" 
           class="flex items-start gap-3 p-2 rounded-xl transition {{ request()->routeIs('reports.*') ? 'bg-brand-50 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70' }}">
            <span class="text-base p-1 rounded-lg bg-slate-100 dark:bg-slate-800">📊</span>
            <div>
                <div class="text-xs font-bold leading-tight">Usage Reports</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">Activity & quota analytics</div>
            </div>
        </a>

        <a href="{{ route('referrals.index') }}" 
           class="flex items-start gap-3 p-2 rounded-xl transition {{ request()->routeIs('referrals.*') ? 'bg-brand-50 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70' }}">
            <span class="text-base p-1 rounded-lg bg-slate-100 dark:bg-slate-800">🎁</span>
            <div>
                <div class="text-xs font-bold leading-tight">Refer & Earn Program</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">Invite peers & get recharge balance</div>
            </div>
        </a>

        <a href="{{ route('wallet.index') }}" 
           class="flex items-start gap-3 p-2 rounded-xl transition {{ request()->routeIs('wallet.*') ? 'bg-brand-50 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70' }}">
            <span class="text-base p-1 rounded-lg bg-slate-100 dark:bg-slate-800">👛</span>
            <div>
                <div class="text-xs font-bold leading-tight">Wallet & Ledger</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">Recharge, balance & deductions</div>
            </div>
        </a>
    </div>
</div>
