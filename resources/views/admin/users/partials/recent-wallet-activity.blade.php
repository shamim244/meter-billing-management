<!-- Recent Wallet Transactions -->
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h2 class="text-sm font-bold text-white flex items-center gap-1.5">
            <span>👛</span> Recent Wallet Activity
        </h2>
        <a href="{{ route('admin.wallets.show', $user->id) }}" class="text-[11px] text-cyan-400 hover:underline font-bold">
            All →
        </a>
    </div>

    <div class="space-y-2.5">
        @forelse($recentTransactions as $tx)
            <div class="p-3 bg-slate-900/60 rounded-2xl border border-slate-800/80 flex items-center justify-between text-xs">
                <div>
                    <div class="font-bold text-white truncate max-w-[170px]">
                        {{ $tx->meta['description'] ?? ($tx->meta['source'] ?? ucfirst($tx->type)) }}
                    </div>
                    <div class="text-[10px] text-slate-500 font-mono">{{ $tx->created_at->format('M d, Y h:i A') }}</div>
                </div>
                <div class="text-right">
                    <div class="font-mono font-bold {{ $tx->type === 'deposit' ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $tx->type === 'deposit' ? '+' : '-' }}₹{{ number_format(abs($tx->amount) / 100, 2) }}
                    </div>
                    <div class="text-[10px] text-slate-500 font-mono uppercase">{{ $tx->type }}</div>
                </div>
            </div>
        @empty
            <div class="py-6 text-center text-xs text-slate-500">
                No wallet transactions recorded.
            </div>
        @endforelse
    </div>
</div>
