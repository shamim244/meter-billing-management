{{-- Header Navigation & Actions --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.wallets.index') }}" class="p-2 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-400 hover:text-white transition">
            ← Back to Wallets
        </a>
        <div>
            <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                <span>👤</span> {{ $user->name }}'s Wallet Console
            </h1>
            <p class="text-xs text-slate-400">Agent Email: <span class="text-slate-300 font-mono">{{ $user->email }}</span> • ID: #{{ $user->id }}</p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.wallets.export', $user->id) }}" class="px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
            <span>📥</span> Export CSV Ledger
        </a>
        <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
            <span>👤</span> View Profile
        </a>
    </div>
</div>
