<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('reports.usage', ['month' => $month, 'year' => $year]) }}" class="text-xs font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                        ← ROI Overview
                    </a>
                </div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5 mt-1">
                    <span>📈</span> Quota Usage & 6-Month Trend Report
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Track MRU and consumer quota consumption, pay-gate triggers, and extra overage expenses over time.
                </p>
            </div>

            <!-- Month/Year Filter -->
            <form method="GET" action="{{ route('reports.quota') }}" class="flex items-center gap-2">
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
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- Current Month Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Active Plan -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Subscribed Plan</span>
                <div class="text-xl font-black text-slate-900 dark:text-white">
                    {{ $quotaUsage['subscription']['plan_name'] ?? 'No Active Subscription' }}
                </div>
                <div class="text-xs text-slate-500 flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                        {{ $quotaUsage['subscription']['status'] ?? 'N/A' }}
                    </span>
                    @if(!empty($quotaUsage['subscription']['expires_at']))
                        <span>Expires: {{ $quotaUsage['subscription']['expires_at'] }}</span>
                    @endif
                </div>
            </div>

            <!-- MRU Quota Usage -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">MRU Quota (Current)</span>
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono">
                    {{ $quotaUsage['mru']['used'] }} <span class="text-sm font-normal text-slate-400">/ {{ $quotaUsage['mru']['included'] }} included</span>
                </div>
                <div class="text-xs text-slate-500">
                    Extra MRUs: <strong class="text-slate-800 dark:text-slate-200">{{ $quotaUsage['mru']['extra'] }}</strong> (₹{{ number_format($quotaUsage['overage_charges']['mru_charges'], 2) }})
                </div>
            </div>

            <!-- Consumer Quota Usage -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Consumer Quota ({{ date('M Y', mktime(0,0,0,$month,1,$year)) }})</span>
                <div class="text-2xl font-black text-blue-600 dark:text-cyan-400 font-mono">
                    {{ number_format($quotaUsage['consumer']['used']) }} <span class="text-sm font-normal text-slate-400">/ {{ number_format($quotaUsage['consumer']['included']) }} included</span>
                </div>
                <div class="text-xs text-slate-500">
                    Extra Consumers: <strong class="text-slate-800 dark:text-slate-200">{{ number_format($quotaUsage['consumer']['extra']) }}</strong> (₹{{ number_format($quotaUsage['overage_charges']['consumer_charges'], 2) }})
                </div>
            </div>
        </div>

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
    </div>
</x-app-layout>
