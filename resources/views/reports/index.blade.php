<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Header & Month Filter -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <span>📊</span> Monthly Usage & ROI Overview
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Monthly operational summary, data coverage, ledger history, and quota intelligence for <strong class="text-slate-800 dark:text-slate-200">{{ $summary['period_label'] }}</strong>.
                </p>
            </div>

            <!-- Month/Year Filter Form -->
            <form method="GET" action="{{ route('reports.usage') }}" class="flex items-center gap-2">
                <select name="month" class="text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                    @endfor
                </select>
                <select name="year" class="text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                    @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition shadow-md shadow-blue-600/20">
                    Filter
                </button>
            </form>
        </div>

        <!-- Quick Sub-Report Links -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <a href="{{ route('reports.status_tag', ['month' => $month, 'year' => $year]) }}" class="p-3.5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-blue-500/50 flex items-center justify-between transition shadow-sm group">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🏷️</span>
                    <div>
                        <div class="text-xs font-bold text-slate-800 dark:text-white group-hover:text-blue-500 transition">Status & Tag Report</div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400">Review status & custom tag breakdown</div>
                    </div>
                </div>
                <span class="text-xs text-blue-500 font-bold">→</span>
            </a>

            <a href="{{ route('reports.quota', ['month' => $month, 'year' => $year]) }}" class="p-3.5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-blue-500/50 flex items-center justify-between transition shadow-sm group">
                <div class="flex items-center gap-3">
                    <span class="text-xl">📈</span>
                    <div>
                        <div class="text-xs font-bold text-slate-800 dark:text-white group-hover:text-blue-500 transition">Quota Usage Report</div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400">MRU / Consumer consumption & trends</div>
                    </div>
                </div>
                <span class="text-xs text-blue-500 font-bold">→</span>
            </a>

            <a href="{{ route('reports.flagged', ['month' => $month, 'year' => $year]) }}" class="p-3.5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-amber-500/50 flex items-center justify-between transition shadow-sm group">
                <div class="flex items-center gap-3">
                    <span class="text-xl">⚠️</span>
                    <div>
                        <div class="text-xs font-bold text-slate-800 dark:text-white group-hover:text-amber-500 transition">Consecutive Estimates</div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ $summary['roi_summary']['flagged_consumers_count'] }} consumers on 2+ LK/MD cycles</div>
                    </div>
                </div>
                <span class="text-xs text-amber-500 font-bold">→</span>
            </a>
        </div>

        <!-- 5-Box ROI Summary Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <!-- 1. Bills Processed -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Bills Processed</span>
                <div class="text-2xl font-black text-blue-600 dark:text-cyan-400 font-mono">
                    {{ number_format($summary['roi_summary']['bills_processed']) }}
                </div>
                <div class="text-[10px] text-slate-400">Parsed this cycle</div>
            </div>

            <!-- 2. Active MRUs -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Active MRUs</span>
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono">
                    {{ $summary['roi_summary']['mrus_active'] }}
                </div>
                <div class="text-[10px] text-slate-400">Under contract</div>
            </div>

            <!-- 3. Data Coverage -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Data Coverage</span>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
                    {{ $summary['roi_summary']['data_coverage_percentage'] }}%
                </div>
                <div class="text-[10px] text-slate-400">{{ $summary['roi_summary']['bills_processed'] }} / {{ $summary['roi_summary']['total_consumers'] }} consumers</div>
            </div>

            <!-- 4. Flagged Estimates -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Consecutive LK/MD</span>
                <div class="text-2xl font-black {{ $summary['roi_summary']['flagged_consumers_count'] > 0 ? 'text-amber-500' : 'text-slate-400' }} font-mono">
                    {{ $summary['roi_summary']['flagged_consumers_count'] }}
                </div>
                <div class="text-[10px] text-slate-400">Needs meter reader inspection</div>
            </div>

            <!-- 5. Historical Depth -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-1 col-span-2 md:col-span-1">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Historical Depth</span>
                <div class="text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">
                    {{ $summary['roi_summary']['historical_depth_months'] }} <span class="text-xs text-slate-400 font-normal">Months</span>
                </div>
                <div class="text-[10px] text-slate-400">Continuous ledger records</div>
            </div>
        </div>

        <!-- 2-Column Section: Status & Tag Breakdown Highlights -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Review Status Breakdown Card -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>📋</span> Review Status Distribution
                    </h2>
                    <a href="{{ route('reports.status_tag', ['month' => $month, 'year' => $year]) }}" class="text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:underline">
                        Details & CSV →
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 rounded-xl text-center">
                        <div class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase">Submitted</div>
                        <div class="text-xl font-black text-emerald-600 dark:text-emerald-300 font-mono mt-0.5">
                            {{ $summary['status_breakdown']['submitted'] }}
                        </div>
                    </div>

                    <div class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 rounded-xl text-center">
                        <div class="text-[10px] font-bold text-rose-700 dark:text-rose-400 uppercase">Critical</div>
                        <div class="text-xl font-black text-rose-600 dark:text-rose-300 font-mono mt-0.5">
                            {{ $summary['status_breakdown']['critical'] }}
                        </div>
                    </div>

                    <div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/40 rounded-xl text-center">
                        <div class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase">Doubt</div>
                        <div class="text-xl font-black text-amber-600 dark:text-amber-300 font-mono mt-0.5">
                            {{ $summary['status_breakdown']['doubt'] }}
                        </div>
                    </div>

                    <div class="p-3 bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-center">
                        <div class="text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase">Pending</div>
                        <div class="text-xl font-black text-slate-700 dark:text-slate-300 font-mono mt-0.5">
                            {{ $summary['status_breakdown']['pending'] }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tag Breakdown Card -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>🏷️</span> Bill Review Tags
                    </h2>
                    <a href="{{ route('reports.status_tag', ['month' => $month, 'year' => $year]) }}" class="text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:underline">
                        Drill Down →
                    </a>
                </div>

                <div class="space-y-2.5">
                    @foreach($summary['tag_breakdown']['tags'] as $tag)
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-{{ $tag['color'] }}-500"></span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $tag['short_label'] }}</span>
                                <span class="text-[10px] text-slate-400">({{ $tag['label'] }})</span>
                            </div>
                            <div class="flex items-center gap-3 font-mono">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $tag['count'] }}</span>
                                <span class="text-[10px] text-slate-400 w-10 text-right">{{ $tag['percentage'] }}%</span>
                            </div>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-{{ $tag['color'] }}-500 transition-all duration-500" style="width: {{ $tag['percentage'] }}%"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Quota & Subscription Status Card -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <span>💳</span> Subscription Plan Quota Utilization
                </h2>
                <a href="{{ route('reports.quota', ['month' => $month, 'year' => $year]) }}" class="text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:underline">
                    6-Month Trend Matrix →
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- MRU Quota -->
                <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">MRU Quota</span>
                        <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">
                            {{ $summary['quota_usage']['mru']['used'] }} / {{ $summary['quota_usage']['mru']['included'] }} Active
                        </span>
                    </div>
                    @php
                        $mruPct = $summary['quota_usage']['mru']['included'] > 0 
                            ? min(100, round(($summary['quota_usage']['mru']['used'] / $summary['quota_usage']['mru']['included']) * 100)) 
                            : 0;
                    @endphp
                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full {{ $summary['quota_usage']['mru']['is_over_quota'] ? 'bg-rose-500' : 'bg-blue-600' }}" style="width: {{ $mruPct }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-slate-500">
                        <span>Extra MRUs: {{ $summary['quota_usage']['mru']['extra'] }}</span>
                        <span>Extra Charges: ₹{{ number_format($summary['quota_usage']['overage_charges']['mru_charges'], 2) }}</span>
                    </div>
                </div>

                <!-- Consumer Quota -->
                <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200/80 dark:border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Consumer Quota</span>
                        <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">
                            {{ number_format($summary['quota_usage']['consumer']['used']) }} / {{ number_format($summary['quota_usage']['consumer']['included']) }} Processed
                        </span>
                    </div>
                    @php
                        $csmPct = $summary['quota_usage']['consumer']['included'] > 0 
                            ? min(100, round(($summary['quota_usage']['consumer']['used'] / $summary['quota_usage']['consumer']['included']) * 100)) 
                            : 0;
                    @endphp
                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full {{ $summary['quota_usage']['consumer']['is_over_quota'] ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $csmPct }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-slate-500">
                        <span>Extra Consumers: {{ number_format($summary['quota_usage']['consumer']['extra']) }}</span>
                        <span>Extra Charges: ₹{{ number_format($summary['quota_usage']['overage_charges']['consumer_charges'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
