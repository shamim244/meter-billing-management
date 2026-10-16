{{-- Comparative Monthly Reading Discrepancy Ledger --}}
<div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
        <div class="flex items-center gap-2">
            <span class="text-xl">📊</span>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Comparative Reading Deviation Ledger</h3>
                <p class="text-[11px] text-slate-500">Side-by-side reconciliation of monthly billed readings vs verified physical readings</p>
            </div>
        </div>
        <span class="text-xs font-mono font-bold text-slate-400">{{ $comparativeLedger->count() }} Billing Cycles</span>
    </div>

    @if($comparativeLedger->isEmpty())
        <div class="py-8 text-center text-xs text-slate-500">
            No historical bill records available for comparative analysis.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-bold">
                        <th class="py-2.5 px-3">Billing Cycle</th>
                        <th class="py-2.5 px-3 text-right">Official Posted</th>
                        <th class="py-2.5 px-3 text-right">Billed Units</th>
                        <th class="py-2.5 px-3 text-right">Bill Amount</th>
                        <th class="py-2.5 px-3 text-right">Nearest Physical</th>
                        <th class="py-2.5 px-3 text-right">Discrepancy (Gap)</th>
                        <th class="py-2.5 px-3 text-center">Audit Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @foreach($comparativeLedger as $row)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2.5 px-3 font-bold font-mono text-slate-900 dark:text-white">
                                {{ $row['billing_period'] }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-600 dark:text-slate-300">
                                {{ number_format($row['posted_reading']) }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-medium">
                                {{ number_format($row['units_consumed']) }} kWh
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-medium text-slate-900 dark:text-white">
                                ₹{{ number_format($row['total_amount'], 2) }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono">
                                @if($row['nearest_physical'] !== null)
                                    <span class="font-bold text-cyan-500">{{ number_format($row['nearest_physical']) }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ $row['nearest_inspection_date'] }}</span>
                                @else
                                    <span class="text-slate-400 italic">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold">
                                @if($row['deviation'] !== null)
                                    @if($row['deviation'] > 0)
                                        <span class="text-rose-500">+{{ number_format($row['deviation']) }} kWh</span>
                                    @elseif($row['deviation'] < 0)
                                        <span class="text-amber-500">{{ number_format($row['deviation']) }} kWh</span>
                                    @else
                                        <span class="text-emerald-500">0 kWh</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @if($row['deviation'] !== null)
                                    @if($row['deviation'] > 0)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-500 border border-rose-500/20">Under-Posted</span>
                                    @elseif($row['deviation'] < 0)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">Over-Posted</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">Accurate</span>
                                    @endif
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-400">Uninspected</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
