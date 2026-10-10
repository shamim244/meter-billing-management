<!-- Section 2: Finance & Payments -->
<div class="space-y-1">
    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-500 tracking-wider">
        Finance & Ledger
    </div>

    <a href="{{ route('admin.wallets.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.wallets.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">👛</span>
        <span>Agent Wallets</span>
    </a>

    @php
        $navPendingPayments = \App\Models\Payment::withoutUserScope()->where('status', \App\Enums\PaymentStatus::PENDING_VERIFICATION->value)->count();
        $isPaymentRoute = request()->routeIs('admin.payments.*');
    @endphp
    <div x-data="{ open: {{ $isPaymentRoute ? 'true' : 'false' }} }" class="space-y-1">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition {{ $isPaymentRoute ? 'bg-slate-900 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
            <div class="flex items-center gap-3">
                <span class="text-base">💳</span>
                <span>Payments & Gateway</span>
            </div>
            <div class="flex items-center gap-2">
                @if($navPendingPayments > 0)
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-amber-500 text-slate-950 animate-pulse">
                        {{ $navPendingPayments }}
                    </span>
                @endif
                <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180 text-indigo-400' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </button>

        <!-- Sub-Menu Links -->
        <div x-show="open" x-cloak class="pl-4 pr-1 py-1 space-y-1 border-l-2 border-slate-800 ml-5 my-1">
            <a href="{{ route('admin.payments.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.payments.index') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                <span>📋</span> All Transactions
            </a>

            <a href="{{ route('admin.payments.manual') }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.payments.manual') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                <div class="flex items-center gap-2.5">
                    <span>⏳</span> Manual Approvals
                </div>
                @if($navPendingPayments > 0)
                    <span class="px-1.5 py-0.2 text-[9px] font-bold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">
                        {{ $navPendingPayments }}
                    </span>
                @endif
            </a>

            <a href="{{ route('admin.payments.analytics') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.payments.analytics') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                <span>📊</span> Revenue Analytics
            </a>

            <a href="{{ route('admin.payments.audit') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.payments.audit') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                <span>📜</span> Audit Trail
            </a>

            <a href="{{ route('admin.payments.settings') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.payments.settings') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                <span>⚙️</span> Gateway Settings
            </a>
        </div>
    </div>
</div>
