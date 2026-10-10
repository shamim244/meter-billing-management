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
