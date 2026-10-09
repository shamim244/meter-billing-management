{{-- Flash Alerts & Frozen Banner --}}
@if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold flex items-center justify-between shadow-lg">
        <span>✅ {{ session('success') }}</span>
        <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
    </div>
@endif

@if(session('error'))
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold flex items-center justify-between shadow-lg">
        <span>❌ {{ session('error') }}</span>
        <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
    </div>
@endif

@if($user->isWalletFrozen())
    <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-800 text-rose-200 text-xs flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <span class="text-lg">🔒</span>
            <div>
                <strong class="text-white block">This Agent's Wallet is Currently FROZEN</strong>
                <span class="text-rose-300 text-[11px]">Reason: {{ $user->wallet_frozen_reason ?: 'Frozen by administrator.' }}</span>
            </div>
        </div>

        <form action="{{ route('admin.wallets.toggle-freeze', $user->id) }}" method="POST">
            @csrf
            <button type="submit" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition">
                🔓 Unfreeze Wallet Now
            </button>
        </form>
    </div>
@endif
