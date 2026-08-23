@php
    $pendingManualCount = \App\Models\Payment::withoutUserScope()->where('status', \App\Enums\PaymentStatus::PENDING_VERIFICATION->value)->count();
@endphp

<div class="flex flex-wrap items-center gap-2 p-1.5 bg-slate-950 rounded-2xl border border-slate-800 shadow-sm mb-6">
    <!-- All Transactions Ledger -->
    <a href="{{ route('admin.payments.index') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.payments.index') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span>📋</span>
        <span>All Transactions</span>
    </a>

    <!-- Manual Verification Queue -->
    <a href="{{ route('admin.payments.manual') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.payments.manual') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span>⏳</span>
        <span>Manual Queue</span>
        @if($pendingManualCount > 0)
            <span class="px-2 py-0.5 text-[10px] font-black rounded-full {{ request()->routeIs('admin.payments.manual') ? 'bg-white text-indigo-700' : 'bg-amber-500 text-slate-950 animate-pulse' }}">
                {{ $pendingManualCount }}
            </span>
        @endif
    </a>

    <!-- Analytics & Reports -->
    <a href="{{ route('admin.payments.analytics') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.payments.analytics') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span>📊</span>
        <span>Analytics & Revenue</span>
    </a>

    <!-- Audit Log -->
    <a href="{{ route('admin.payments.audit') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.payments.audit') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span>📜</span>
        <span>Audit Trail</span>
    </a>

    <!-- Test Simulator -->
    <a href="{{ route('admin.payments.simulator') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.payments.simulator*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span>🧪</span>
        <span>Sandbox & Test Simulator</span>
    </a>

    <!-- Gateway & Channel Settings -->
    <a href="{{ route('admin.payments.settings') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.payments.settings') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span>⚙️</span>
        <span>Gateway Settings</span>
    </a>
</div>
