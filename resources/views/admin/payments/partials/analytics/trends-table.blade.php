<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
    <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
        <span>📅</span> 6-Month Revenue Trend
    </h3>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/80 text-[10px] uppercase text-slate-400 border-b border-slate-800 font-bold">
                <tr>
                    <th class="py-2.5 px-3">Month</th>
                    <th class="py-2.5 px-3">Transactions</th>
                    <th class="py-2.5 px-3 text-right">Revenue Collected</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono">
                @foreach($monthlyTrend as $m)
                    <tr class="hover:bg-slate-900/40">
                        <td class="py-2.5 px-3 font-sans font-bold text-white">{{ $m['month'] }}</td>
                        <td class="py-2.5 px-3 text-slate-400">{{ $m['count'] }}</td>
                        <td class="py-2.5 px-3 text-right font-black text-cyan-400">₹{{ number_format($m['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
