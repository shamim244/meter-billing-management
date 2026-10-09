{{-- Central Wallet Card with 2 Adjustment Buttons & Freeze Toggle (PRD Section 7.4) --}}
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">

        {{-- Left: Clear Prominent Balance Display --}}
        <div class="space-y-1.5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <span>👛</span> Current Wallet Balance
            </span>
            <div class="text-4xl font-black font-mono tracking-tight {{ $balance < 0 ? 'text-rose-400' : ($balance < 200 ? 'text-amber-400' : 'text-emerald-400') }}">
                ₹{{ number_format($balance, 2) }}
            </div>
            <div class="text-[11px] text-slate-400 flex items-center gap-2">
                <span>Currency: <strong class="text-white font-mono">INR</strong></span>
                <span>•</span>
                <span>Status: <strong class="{{ $user->isWalletFrozen() ? 'text-rose-400' : 'text-emerald-400' }}">{{ $user->isWalletFrozen() ? 'FROZEN' : 'ACTIVE' }}</strong></span>
            </div>
        </div>

        {{-- Right: Fast Actions: [+ Add Balance] and [− Deduct Balance] (Two Buttons Only per PRD) --}}
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" @click="openModal('add')" class="px-5 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-bold shadow-lg shadow-emerald-600/30 hover:scale-[1.02] active:scale-[0.98] transition flex items-center gap-2">
                <span class="text-base font-black">+</span> Add Balance
            </button>

            <button type="button" @click="openModal('deduct')" class="px-5 py-3 bg-rose-600 hover:bg-rose-500 text-white rounded-2xl text-xs font-bold shadow-lg shadow-rose-600/30 hover:scale-[1.02] active:scale-[0.98] transition flex items-center gap-2">
                <span class="text-base font-black">−</span> Deduct Balance
            </button>

            @if(!$user->isWalletFrozen())
                <button type="button" @click="openFreezeModal()" class="px-4 py-3 bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 rounded-2xl text-xs font-bold transition flex items-center gap-1.5">
                    <span>🔒</span> Freeze
                </button>
            @else
                <form action="{{ route('admin.wallets.toggle-freeze', $user->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-3 bg-emerald-600/20 border border-emerald-500/30 hover:bg-emerald-600/30 text-emerald-300 rounded-2xl text-xs font-bold transition flex items-center gap-1.5">
                        <span>🔓</span> Unfreeze Wallet
                    </button>
                </form>
            @endif
        </div>

    </div>

    {{-- Quick Stats Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-900 text-xs">
        <div>
            <span class="text-slate-500 block text-[10px] uppercase font-bold">Total Credited</span>
            <span class="text-base font-black font-mono text-emerald-400">₹{{ number_format($stats['total_credited'], 2) }}</span>
        </div>
        <div>
            <span class="text-slate-500 block text-[10px] uppercase font-bold">Total Debited</span>
            <span class="text-base font-black font-mono text-slate-200">₹{{ number_format($stats['total_debited'], 2) }}</span>
        </div>
        <div>
            <span class="text-slate-500 block text-[10px] uppercase font-bold">Ledger Transactions</span>
            <span class="text-base font-black font-mono text-white">{{ number_format($stats['transaction_count']) }}</span>
        </div>
        <div>
            <span class="text-slate-500 block text-[10px] uppercase font-bold">Admin Adjustments</span>
            <span class="text-base font-black font-mono text-indigo-400">{{ number_format($stats['adjustment_count']) }}</span>
        </div>
    </div>
</div>
