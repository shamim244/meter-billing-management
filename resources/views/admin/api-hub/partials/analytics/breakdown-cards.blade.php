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
