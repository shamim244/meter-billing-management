<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>💳</span> Quota Usage & Overage Leaderboard
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Identify high-volume Billing Agents and upsell candidates exceeding included subscription quotas.
                </p>
            </div>

            <!-- Month & Sorting Filter -->
            <form method="GET" action="{{ route('admin.reports.quota') }}" class="flex flex-wrap items-center gap-2">
                <select name="sort_by" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
                    <option value="overage_spend" {{ $sortBy === 'overage_spend' ? 'selected' : '' }}>Sort: Highest Overage Spend</option>
                    <option value="consumer_usage" {{ $sortBy === 'consumer_usage' ? 'selected' : '' }}>Sort: Most Consumers Processed</option>
                    <option value="mru_usage" {{ $sortBy === 'mru_usage' ? 'selected' : '' }}>Sort: Most Active MRUs</option>
                    <option value="name" {{ $sortBy === 'name' ? 'selected' : '' }}>Sort: Agent Name (A-Z)</option>
                </select>
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
            <a href="{{ route('admin.reports.index', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                📊 Platform Overview
            </a>
            <a href="{{ route('admin.reports.status_tag', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                🏷️ Status & Tag Distribution
            </a>
            <a href="{{ route('admin.reports.quota', ['month' => $month, 'year' => $year, 'sort_by' => $sortBy]) }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-lg shadow-indigo-600/30">
                💳 Quota & Overage Leaderboard
            </a>
            <a href="{{ route('admin.reports.flagged', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                ⚠️ Consecutive Estimates
            </a>
        </div>

        <!-- Summary Totals -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-slate-900 p-4 rounded-2xl border border-slate-800 shadow-xl space-y-1">
                <span class="text-[11px] font-semibold text-slate-400 uppercase">Total Overage Spend</span>
                <div class="text-2xl font-black text-rose-400 font-mono">
                    ₹{{ number_format($aggregate['totals']['total_overage_spend'], 2) }}
                </div>
            </div>
            <div class="bg-slate-900 p-4 rounded-2xl border border-slate-800 shadow-xl space-y-1">
                <span class="text-[11px] font-semibold text-slate-400 uppercase">Extra MRU Fees</span>
                <div class="text-2xl font-black text-indigo-400 font-mono">
                    ₹{{ number_format($aggregate['totals']['total_mru_charges'], 2) }}
                </div>
            </div>
            <div class="bg-slate-900 p-4 rounded-2xl border border-slate-800 shadow-xl space-y-1">
                <span class="text-[11px] font-semibold text-slate-400 uppercase">Extra Consumer Fees</span>
                <div class="text-2xl font-black text-cyan-400 font-mono">
                    ₹{{ number_format($aggregate['totals']['total_consumer_charges'], 2) }}
                </div>
            </div>
            <div class="bg-slate-900 p-4 rounded-2xl border border-slate-800 shadow-xl space-y-1">
                <span class="text-[11px] font-semibold text-slate-400 uppercase">Active MRUs Monitored</span>
                <div class="text-2xl font-black text-emerald-400 font-mono">
                    {{ $aggregate['totals']['total_mrus_used'] }}
                </div>
            </div>
        </div>

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
    </div>
</x-admin-layout>
