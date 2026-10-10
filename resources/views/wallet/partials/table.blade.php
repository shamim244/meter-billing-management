<div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
            <span>📜</span> Transaction Ledger (Immutable Record)
        </h2>
        <span class="text-xs text-slate-400">Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
            <thead class="bg-slate-50/75 dark:bg-slate-950/60 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100 dark:border-slate-800">
                <tr>
                    <th class="py-3 px-4">Tx ID</th>
                    <th class="py-3 px-4">Date & Time</th>
                    <th class="py-3 px-4">Type</th>
                    <th class="py-3 px-4">Source</th>
                    <th class="py-3 px-4">Amount</th>
                    <th class="py-3 px-4">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono text-xs">
                @forelse($transactions as $tx)
                    @php
                        $typeVal = $tx->type instanceof \BackedEnum ? $tx->type->value : (string) $tx->type;
                        $isCredit = $typeVal === 'deposit';
                        $meta = (array) ($tx->meta ?? []);
                        $source = $meta['source'] ?? ($isCredit ? 'Credit' : 'Debit');
                        $desc = $meta['description'] ?? ($meta['reason'] ?? '—');
                    @endphp
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-950/40 transition">
                        <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">#{{ $tx->id }}</td>
                        <td class="py-3.5 px-4 font-sans text-slate-500 dark:text-slate-400 text-[11px]">
                            {{ $tx->created_at->format('d M Y, h:i A') }}
                        </td>
                        <td class="py-3.5 px-4 font-sans">
                            @if($isCredit)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60">
                                    + CREDIT
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60">
                                    − DEBIT
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-sans font-semibold text-slate-700 dark:text-slate-300 text-[11px]">
                            {{ ucwords(str_replace('_', ' ', $source)) }}
                        </td>
                        <td class="py-3.5 px-4 font-black {{ $isCredit ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                            {{ $isCredit ? '+' : '−' }}₹{{ number_format(abs((float)$tx->amountFloat), 2) }}
                        </td>
                        <td class="py-3.5 px-4 font-sans text-slate-600 dark:text-slate-400 text-[11px] max-w-xs truncate">
                            {{ $desc }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400 font-sans text-xs">
                            No transactions recorded in wallet ledger yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
