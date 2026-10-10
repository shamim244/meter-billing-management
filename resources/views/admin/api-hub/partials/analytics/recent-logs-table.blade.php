<!-- Recent Requests Activity Table -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4">
    <h4 class="text-sm font-bold text-white flex items-center gap-2">
        <span>⚡</span> Recent Request Stream (Live Telemetry)
    </h4>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="text-[10px] uppercase font-bold text-slate-500 bg-slate-900/60 rounded-xl">
                <tr>
                    <th class="p-3 rounded-l-xl">Status</th>
                    <th class="p-3">Method</th>
                    <th class="p-3">Endpoint Path</th>
                    <th class="p-3">User / Key</th>
                    <th class="p-3">Latency</th>
                    <th class="p-3">IP Address</th>
                    <th class="p-3 rounded-r-xl">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono">
                @forelse ($recentLogs as $log)
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="p-3">
                            @if ($log->status_code >= 200 && $log->status_code < 300)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">{{ $log->status_code }}</span>
                            @elseif ($log->status_code === 429)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500/15 text-amber-400 border border-amber-500/30">{{ $log->status_code }}</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500/15 text-rose-400 border border-rose-500/30">{{ $log->status_code }}</span>
                            @endif
                        </td>
                        <td class="p-3 font-bold text-slate-200">{{ $log->method }}</td>
                        <td class="p-3 text-cyan-300 font-medium">{{ $log->path }}</td>
                        <td class="p-3 text-slate-400 font-sans">
                            {{ $log->user ? $log->user->name : ($log->apiKey ? $log->apiKey->name : 'Unauthenticated') }}
                        </td>
                        <td class="p-3 text-slate-300">{{ $log->duration_ms }}ms</td>
                        <td class="p-3 text-slate-500">{{ $log->ip_address }}</td>
                        <td class="p-3 text-slate-500 font-sans">{{ $log->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-slate-500 font-sans">
                            No API requests logged yet. Calls made to <code class="text-cyan-400">/api/v1/*</code> will stream here automatically.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
