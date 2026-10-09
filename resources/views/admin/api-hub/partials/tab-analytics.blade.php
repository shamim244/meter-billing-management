<!-- ========================================== -->
<!-- TAB 1: TRAFFIC ANALYTICS                   -->
<!-- ========================================== -->
<div x-show="activeTab === 'analytics'" class="space-y-6" x-cloak>
    <!-- 14-Day Timeline Bar Chart -->
    <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <span>📈</span> Daily Request Volume (Last 14 Days)
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Track automated request trends and surge patterns.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono text-slate-400">Total Recorded: <strong class="text-white">{{ number_format($totalRequests) }}</strong></span>
            </div>
        </div>

        @php
            $maxCount = max(1, max(array_column($dailyTimeline, 'count')));
        @endphp

        <!-- Visual Bar Timeline -->
        <div class="h-44 flex items-end gap-2 pt-6 pb-2 px-2 border-b border-slate-800 overflow-x-auto">
            @foreach ($dailyTimeline as $day)
                @php
                    $heightPercent = max(6, (int) round(($day['count'] / $maxCount) * 100));
                @endphp
                <div class="flex-1 min-w-[28px] flex flex-col items-center gap-1 group relative">
                    <!-- Tooltip -->
                    <div class="absolute -top-9 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none bg-slate-900 border border-slate-700 text-white text-[10px] font-mono px-2 py-1 rounded-lg shadow-xl whitespace-nowrap z-20">
                        {{ $day['label'] }}: {{ $day['count'] }} requests
                    </div>
                    <!-- Bar -->
                    <div class="w-full rounded-t-lg bg-gradient-to-t from-indigo-600 to-cyan-400 group-hover:from-indigo-500 group-hover:to-cyan-300 transition shadow-sm"
                        style="height: {{ $heightPercent }}%;"></div>
                    <!-- Label -->
                    <span class="text-[9px] font-mono text-slate-500 group-hover:text-slate-300 transition">{{ $day['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Distribution Breakdown Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Endpoint Groups Distribution -->
        <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
            <h4 class="text-sm font-bold text-white flex items-center gap-2">
                <span>🧭</span> Requests by Endpoint Group
            </h4>

            <div class="space-y-3 pt-2">
                @php
                    $groups = [
                        'reads' => ['label' => 'General Reads & Queue', 'color' => 'bg-cyan-500'],
                        'reviews' => ['label' => 'Review Submissions', 'color' => 'bg-amber-500'],
                        'batch' => ['label' => 'Offline Batch Sync', 'color' => 'bg-purple-500'],
                        'automation' => ['label' => 'Python ADB Tool', 'color' => 'bg-emerald-500'],
                        'sync' => ['label' => 'Flutter Mobile Sync', 'color' => 'bg-blue-500'],
                        'auth' => ['label' => 'Auth & Login', 'color' => 'bg-rose-500'],
                        'docs' => ['label' => 'OpenAPI & Docs', 'color' => 'bg-teal-500'],
                    ];
                @endphp

                @foreach ($groups as $key => $meta)
                    @php
                        $cnt = $groupCounts[$key] ?? 0;
                        $pct = $totalRequests > 0 ? round(($cnt / $totalRequests) * 100, 1) : 0;
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-300 font-medium">{{ $meta['label'] }}</span>
                            <span class="font-mono text-slate-400">{{ number_format($cnt) }} <span class="text-[10px] text-slate-500">({{ $pct }}%)</span></span>
                        </div>
                        <div class="w-full h-2 bg-slate-900 rounded-full overflow-hidden">
                            <div class="h-full {{ $meta['color'] }} rounded-full" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- HTTP Status Health -->
        <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>🚦</span> HTTP Response Status Code Health
                </h4>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <div class="bg-slate-900/80 p-4 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-400">2xx Success</span>
                        <span class="text-base">🟢</span>
                    </div>
                    <div class="text-xl font-black text-white font-mono mt-2">{{ number_format($status2xx) }}</div>
                    <div class="text-[10px] text-slate-500 mt-1">Processed successfully</div>
                </div>

                <div class="bg-slate-900/80 p-4 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-400">429 Throttled</span>
                        <span class="text-base">⏳</span>
                    </div>
                    <div class="text-xl font-black text-amber-300 font-mono mt-2">{{ number_format($status429) }}</div>
                    <div class="text-[10px] text-slate-500 mt-1">Rate limit triggered</div>
                </div>

                <div class="bg-slate-900/80 p-4 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-sky-400">4xx Client Errors</span>
                        <span class="text-base">🟡</span>
                    </div>
                    <div class="text-xl font-black text-white font-mono mt-2">{{ number_format($status4xx) }}</div>
                    <div class="text-[10px] text-slate-500 mt-1">Bad auth / validation</div>
                </div>

                <div class="bg-slate-900/80 p-4 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-rose-400">5xx Server Errors</span>
                        <span class="text-base">🔴</span>
                    </div>
                    <div class="text-xl font-black text-rose-400 font-mono mt-2">{{ number_format($status5xx) }}</div>
                    <div class="text-[10px] text-slate-500 mt-1">Internal exceptions</div>
                </div>
            </div>

            <!-- Telemetry Maintenance Button -->
            <div class="pt-2 flex justify-end">
                <button type="button" @click="confirmClearAnalytics = true" class="text-xs text-slate-500 hover:text-rose-400 transition">
                    🧹 Purge Historical Logs
                </button>
            </div>
        </div>
    </div>

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
</div>
