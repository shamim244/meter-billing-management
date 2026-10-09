{{-- Ledger Transactions Table --}}
<div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
    <div class="p-4 border-b border-slate-800 flex items-center justify-between">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
            <span>📜</span> Full Ledger Activity (Immutable)
        </h2>
        <span class="text-xs text-slate-500">Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/80 text-[10px] uppercase text-slate-400 border-b border-slate-800 font-bold">
                <tr>
                    <th class="py-3 px-4">Tx ID</th>
                    <th class="py-3 px-4">Timestamp</th>
                    <th class="py-3 px-4">Type</th>
                    <th class="py-3 px-4">Source</th>
                    <th class="py-3 px-4">Amount</th>
                    <th class="py-3 px-4">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono text-xs">
                @forelse($transactions as $tx)
                    @php
                        $typeVal = $tx->type instanceof \BackedEnum ? $tx->type->value : (string) $tx->type;
                        $isCredit = $typeVal === 'deposit';
                        $meta = (array) ($tx->meta ?? []);
                        $source = $meta['source'] ?? ($isCredit ? 'Credit' : 'Debit');
                        $desc = $meta['description'] ?? ($meta['reason'] ?? '—');
                    @endphp
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="py-3 px-4 font-bold text-white">#{{ $tx->id }}</td>
                        <td class="py-3 px-4 font-sans text-slate-400 text-[11px]">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                        <td class="py-3 px-4 font-sans">
                            @if($isCredit)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">+ CREDIT</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">− DEBIT</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-sans text-indigo-300 font-semibold text-[11px]">{{ ucwords(str_replace('_', ' ', $source)) }}</td>
                        <td class="py-3 px-4 font-black {{ $isCredit ? 'text-emerald-400' : 'text-white' }}">
                            {{ $isCredit ? '+' : '−' }}₹{{ number_format(abs((float)$tx->amountFloat), 2) }}
                        </td>
                        <td class="py-3 px-4 font-sans text-slate-300 text-[11px] max-w-xs truncate">{{ $desc }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500 font-sans">No transactions recorded for this wallet yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
