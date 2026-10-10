<!-- Leaderboard Table -->
<div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                    <th class="py-3 px-3">Agent</th>
                    <th class="py-3 px-3">Plan</th>
                    <th class="py-3 px-3 text-center">MRUs (Used / Inc)</th>
                    <th class="py-3 px-3 text-center">Extra MRUs</th>
                    <th class="py-3 px-3 text-center">Consumers (Used / Inc)</th>
                    <th class="py-3 px-3 text-center">Extra Consumers</th>
                    <th class="py-3 px-3 text-right">MRU Overage (₹)</th>
                    <th class="py-3 px-3 text-right">Consumer Overage (₹)</th>
                    <th class="py-3 px-3 text-right font-bold">Total Overage Spend (₹)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300 font-mono">
                @foreach($aggregate['rows'] as $row)
                    <tr class="hover:bg-slate-800/20 transition {{ $row['overage_spend'] > 0 ? 'bg-rose-950/10' : '' }}">
                        <td class="py-3 px-3 font-sans">
                            <div class="font-bold text-white">{{ $row['name'] }}</div>
                            <div class="text-[10px] text-slate-500 font-mono">{{ $row['email'] }}</div>
                        </td>
                        <td class="py-3 px-3 font-sans">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-950 border border-slate-800 text-slate-300">
                                {{ $row['plan_name'] }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center">
                            {{ $row['mru_used'] }} / {{ $row['mru_included'] }}
                        </td>
                        <td class="py-3 px-3 text-center {{ $row['mru_extra'] > 0 ? 'text-rose-400 font-bold' : 'text-slate-500' }}">
                            {{ $row['mru_extra'] }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            {{ number_format($row['consumer_used']) }} / {{ number_format($row['consumer_included']) }}
                        </td>
                        <td class="py-3 px-3 text-center {{ $row['consumer_extra'] > 0 ? 'text-rose-400 font-bold' : 'text-slate-500' }}">
                            {{ number_format($row['consumer_extra']) }}
                        </td>
                        <td class="py-3 px-3 text-right text-slate-300">
                            ₹{{ number_format($row['mru_charges'], 2) }}
                        </td>
                        <td class="py-3 px-3 text-right text-slate-300">
                            ₹{{ number_format($row['consumer_charges'], 2) }}
                        </td>
                        <td class="py-3 px-3 text-right font-black {{ $row['overage_spend'] > 0 ? 'text-rose-400' : 'text-slate-500' }}">
                            ₹{{ number_format($row['overage_spend'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
