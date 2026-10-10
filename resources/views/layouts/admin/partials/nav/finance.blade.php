@php
    $isFinanceActive = request()->routeIs('admin.wallets.*', 'admin.payments.*', 'admin.plans.*', 'admin.subscriptions.*');
    $navPendingPayments = \App\Models\Payment::withoutUserScope()->where('status', \App\Enums\PaymentStatus::PENDING_VERIFICATION->value)->count();
@endphp

<!-- Pillar 2: Finance & Gateways -->
<div x-data="{ open: {{ $isFinanceActive ? 'true' : 'false' }} }" class="space-y-0.5">
    <button type="button" 
            @click="open = !open" 
            class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition text-xs font-bold {{ $isFinanceActive ? 'bg-slate-900 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-900/50' }}">
        <div class="flex items-center gap-2.5">
            <span class="text-sm">💳</span>
            <span class="tracking-wide">Finance & Gateways</span>
        </div>
        <div class="flex items-center gap-1.5">
            @if($navPendingPayments > 0)
                <span class="px-1.5 py-0.2 text-[9px] font-black rounded-full bg-amber-500 text-slate-950 font-mono animate-pulse">
                    {{ $navPendingPayments }}
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
        <a href="{{ route('admin.wallets.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.wallets.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>👛</span>
            <span>Agent Wallets</span>
        </a>

        <!-- Payments Nested Sub-Group -->
        <div x-data="{ pOpen: {{ request()->routeIs('admin.payments.*') ? 'true' : 'false' }} }" class="space-y-0.5">
            <button type="button" @click="pOpen = !pOpen" class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-[11px] font-medium transition {{ request()->routeIs('admin.payments.*') ? 'text-indigo-300 bg-slate-900/60 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-900/40' }}">
                <div class="flex items-center gap-2">
                    <span>💳</span>
                    <span>Payments & Gateway</span>
                </div>
                <div class="flex items-center gap-1">
                    @if($navPendingPayments > 0)
                        <span class="px-1.5 py-0.2 text-[9px] font-bold rounded-full bg-amber-500/20 text-amber-400">
                            {{ $navPendingPayments }}
                        </span>
                    @endif
                    <svg class="w-3 h-3 text-slate-500 transition-transform" :class="pOpen ? 'rotate-180 text-indigo-400' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>

            <div x-show="pOpen" x-cloak class="pl-3 ml-2 border-l border-slate-800/80 space-y-0.5 py-0.5">
                <a href="{{ route('admin.payments.index') }}" class="flex items-center gap-2 px-2 py-1 rounded text-[11px] {{ request()->routeIs('admin.payments.index') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                    <span>📋</span> All Transactions
                </a>
                <a href="{{ route('admin.payments.manual') }}" class="flex items-center justify-between px-2 py-1 rounded text-[11px] {{ request()->routeIs('admin.payments.manual') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                    <span class="flex items-center gap-2"><span>⏳</span> Manual Approvals</span>
                    @if($navPendingPayments > 0)
                        <span class="text-[9px] font-bold text-amber-400">({{ $navPendingPayments }})</span>
                    @endif
                </a>
                <a href="{{ route('admin.payments.analytics') }}" class="flex items-center gap-2 px-2 py-1 rounded text-[11px] {{ request()->routeIs('admin.payments.analytics') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                    <span>📊</span> Revenue Analytics
                </a>
                <a href="{{ route('admin.payments.audit') }}" class="flex items-center gap-2 px-2 py-1 rounded text-[11px] {{ request()->routeIs('admin.payments.audit') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                    <span>📜</span> Audit Trail
                </a>
                <a href="{{ route('admin.payments.settings') }}" class="flex items-center gap-2 px-2 py-1 rounded text-[11px] {{ request()->routeIs('admin.payments.settings') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                    <span>⚙️</span> Gateway Settings
                </a>
            </div>
        </div>

        <a href="{{ route('admin.plans.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.plans.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>📋</span>
            <span>Subscription Plans</span>
        </a>

        <a href="{{ route('admin.subscriptions.index') }}" 
           class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-[11px] transition {{ request()->routeIs('admin.subscriptions.*') ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
            <span>🔄</span>
            <span>Agent Subscriptions</span>
        </a>
    </div>
</div>
