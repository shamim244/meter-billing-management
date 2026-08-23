<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>📊</span> Platform Usage & Account Health
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    System-wide operational volume, data coverage, consecutive estimate alerts, and overage revenue.
                </p>
            </div>

            <!-- Month/Year Filter -->
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2">
                <select name="month" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                    @endfor
                </select>
                <select name="year" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
                    @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- Sub-Report Navigation Tabs -->
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.reports.index', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-lg shadow-indigo-600/30">
                📊 Platform Overview
            </a>
            <a href="{{ route('admin.reports.status_tag', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                🏷️ Status & Tag Distribution
            </a>
            <a href="{{ route('admin.reports.quota', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                💳 Quota & Overage Leaderboard
            </a>
            <a href="{{ route('admin.reports.flagged', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                ⚠️ Consecutive Estimates
            </a>
        </div>

        <!-- Platform KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-1">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Bills Processed</span>
                <div class="text-2xl font-black text-cyan-400 font-mono">
                    {{ number_format($summary['totals']['total_bills_processed']) }}
                </div>
                <div class="text-[10px] text-slate-500">{{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</div>
            </div>

            <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-1">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Active MRUs Platform-Wide</span>
                <div class="text-2xl font-black text-indigo-400 font-mono">
                    {{ number_format($summary['totals']['total_active_mrus']) }}
                </div>
                <div class="text-[10px] text-slate-500">Across {{ $summary['totals']['total_agents'] }} Billing Agents</div>
            </div>

            <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-1">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Consecutive LK/MD Alerts</span>
                <div class="text-2xl font-black text-amber-400 font-mono">
                    {{ number_format($summary['totals']['total_flagged_consumers']) }}
                </div>
                <div class="text-[10px] text-slate-500">2+ cycles on estimate</div>
            </div>

            <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-1">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Active Billing Agents</span>
                <div class="text-2xl font-black text-emerald-400 font-mono">
                    {{ $summary['totals']['total_agents'] }}
                </div>
                <div class="text-[10px] text-slate-500">Registered platform accounts</div>
            </div>
        </div>

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
    </div>
</x-admin-layout>
