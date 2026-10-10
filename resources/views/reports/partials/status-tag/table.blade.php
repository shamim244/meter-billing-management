<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
            Matching Consumers ({{ $consumers->total() }})
        </h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider">
                    <th class="py-3 px-3">CA Number</th>
                    <th class="py-3 px-3">Consumer Name</th>
                    <th class="py-3 px-3">MRU</th>
                    <th class="py-3 px-3 text-center">Basis</th>
                    <th class="py-3 px-3 text-right">Units</th>
                    <th class="py-3 px-3 text-right">Amount (₹)</th>
                    <th class="py-3 px-3 text-center">Review Status</th>
                    <th class="py-3 px-3 text-center">Tag</th>
                    <th class="py-3 px-3">Remark</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                @forelse($consumers as $bill)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                        <td class="py-2.5 px-3 font-mono font-bold text-blue-600 dark:text-cyan-400">
                            {{ $bill->ca_number }}
                        </td>
                        <td class="py-2.5 px-3 font-medium text-slate-900 dark:text-white">
                            {{ $bill->consumer_name }}
                        </td>
                        <td class="py-2.5 px-3 text-slate-500 font-mono text-[11px]">
                            {{ $bill->mru ? $bill->mru->code : 'GENERAL' }}
                        </td>
                        <td class="py-2.5 px-3 text-center font-mono font-bold text-[11px]">
                            <span class="px-1.5 py-0.5 rounded {{ $bill->billing_basis === 'OK' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                {{ $bill->billing_basis ?: 'OK' }}
                            </span>
                        </td>
                        <td class="py-2.5 px-3 text-right font-mono font-semibold">
                            {{ number_format($bill->units_consumed ?? 0) }}
                        </td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                            ₹{{ number_format($bill->total_amount, 2) }}
                        </td>
                        <td class="py-2.5 px-3 text-center">
                            @php
                                $st = $bill->resolved_status ?? 'pending';
                                $statusClass = match($st) {
                                    'submitted' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                                    'critical' => 'bg-rose-500/20 text-rose-400 border-rose-500/30',
                                    'doubt' => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                                    default => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border {{ $statusClass }}">
                                {{ $st }}
                            </span>
                        </td>
                        <td class="py-2.5 px-3 text-center">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                {{ $bill->resolved_tag ?? ($bill->tag ?: 'OK') }}
                            </span>
                        </td>
                        <td class="py-2.5 px-3 text-slate-500 text-[11px] max-w-[200px] truncate">
                            {{ $bill->resolved_remark ?? ($bill->remark ?: '—') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            No bills match the selected month, status, and tag filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($consumers->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            {{ $consumers->withQueryString()->links() }}
        </div>
    @endif
</div>
