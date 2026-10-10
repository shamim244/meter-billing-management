<!-- Per-Agent Breakdown Table -->
<div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
    <div class="p-5 border-b border-slate-800/80">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Agent Health & Usage Matrix</h2>
        <p class="text-xs text-slate-400 mt-0.5">Spot Agents with low data coverage or high overage spend.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                    <th class="py-3 px-3">Agent</th>
                    <th class="py-3 px-3 text-center">Active MRUs</th>
                    <th class="py-3 px-3 text-center">Bills Processed</th>
                    <th class="py-3 px-3 text-center">Data Coverage</th>
                    <th class="py-3 px-3 text-center">Flagged Estimates</th>
                    <th class="py-3 px-3 text-right">Overage Spend</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                @foreach($summary['agent_breakdown'] as $agent)
                    <tr class="hover:bg-slate-800/20 transition">
                        <td class="py-3 px-3">
                            <div class="font-bold text-white">{{ $agent['name'] }}</div>
                            <div class="text-[10px] text-slate-500 font-mono">{{ $agent['email'] }}</div>
                        </td>
                        <td class="py-3 px-3 text-center font-mono font-semibold">
                            {{ $agent['mrus_active'] }}
                        </td>
                        <td class="py-3 px-3 text-center font-mono font-semibold text-cyan-400">
                            {{ number_format($agent['bills_processed']) }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2 py-0.5 rounded font-mono font-bold text-[11px] {{ $agent['data_coverage'] < 50 ? 'bg-rose-500/20 text-rose-300' : 'bg-emerald-500/20 text-emerald-300' }}">
                                {{ $agent['data_coverage'] }}%
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center font-mono font-bold {{ $agent['flagged_consumers'] > 0 ? 'text-amber-400' : 'text-slate-500' }}">
                            {{ $agent['flagged_consumers'] }}
                        </td>
                        <td class="py-3 px-3 text-right font-mono font-bold {{ $agent['overage_spend'] > 0 ? 'text-rose-400' : 'text-slate-500' }}">
                            ₹{{ number_format($agent['overage_spend'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
