<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
    <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
        <span>👑</span> Top Billing Agents by Volume
    </h3>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/80 text-[10px] uppercase text-slate-400 border-b border-slate-800 font-bold">
                <tr>
                    <th class="py-2.5 px-3">Billing Agent</th>
                    <th class="py-2.5 px-3">Payments</th>
                    <th class="py-2.5 px-3 text-right">Total Contributed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($topAgents as $agent)
                    <tr class="hover:bg-slate-900/40">
                        <td class="py-2.5 px-3">
                            <div class="font-bold text-white">{{ $agent->user->name ?? 'Agent #' . $agent->user_id }}</div>
                            <div class="text-[10px] text-slate-400">{{ $agent->user->email ?? '' }}</div>
                        </td>
                        <td class="py-2.5 px-3 font-mono text-slate-400">{{ $agent->transaction_count }}</td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-400">₹{{ number_format((float)$agent->total_spent, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-6 text-center text-slate-500">No payment data yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
