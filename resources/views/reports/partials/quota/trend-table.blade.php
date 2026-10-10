<!-- 6-Month Trend Matrix Table -->
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden space-y-4 p-5">
    <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
            6-Month Historical Usage & Overage Trend
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Month-over-month consumption patterns and wallet overage spend breakdown.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider">
                    <th class="py-3 px-3">Month</th>
                    <th class="py-3 px-3 text-center">MRUs (Used / Inc)</th>
                    <th class="py-3 px-3 text-center">Extra MRUs</th>
                    <th class="py-3 px-3 text-center">Consumers (Used / Inc)</th>
                    <th class="py-3 px-3 text-center">Extra Consumers</th>
                    <th class="py-3 px-3 text-right">MRU Fees (₹)</th>
                    <th class="py-3 px-3 text-right">Consumer Fees (₹)</th>
                    <th class="py-3 px-3 text-right">Total Overage (₹)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300 font-mono">
                @foreach($trend as $row)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition {{ ($row['month'] === $month && $row['year'] === $year) ? 'bg-blue-50/50 dark:bg-blue-950/20 font-bold' : '' }}">
                        <td class="py-3 px-3 font-sans font-semibold text-slate-900 dark:text-white">
                            {{ $row['label'] }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            {{ $row['mru_used'] }} / {{ $row['mru_included'] }}
                        </td>
                        <td class="py-3 px-3 text-center {{ $row['mru_extra'] > 0 ? 'text-rose-500 font-bold' : 'text-slate-400' }}">
                            {{ $row['mru_extra'] }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            {{ number_format($row['consumer_used']) }} / {{ number_format($row['consumer_included']) }}
                        </td>
                        <td class="py-3 px-3 text-center {{ $row['consumer_extra'] > 0 ? 'text-rose-500 font-bold' : 'text-slate-400' }}">
                            {{ number_format($row['consumer_extra']) }}
                        </td>
                        <td class="py-3 px-3 text-right text-slate-700 dark:text-slate-300">
                            ₹{{ number_format($row['mru_charges'], 2) }}
                        </td>
                        <td class="py-3 px-3 text-right text-slate-700 dark:text-slate-300">
                            ₹{{ number_format($row['consumer_charges'], 2) }}
                        </td>
                        <td class="py-3 px-3 text-right font-black {{ $row['total_charges'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">
                            ₹{{ number_format($row['total_charges'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
