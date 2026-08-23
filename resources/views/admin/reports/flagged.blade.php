<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>⚠️</span> Platform Consecutive Estimate Queue
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    System-wide list of consumers on 2+ consecutive LK (Locked) or MD (Defective Meter) cycles across all Billing Agents.
                </p>
            </div>

            <!-- Filter -->
            <form method="GET" action="{{ route('admin.reports.flagged') }}" class="flex flex-wrap items-center gap-2">
                <select name="agent_id" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
                    <option value="">All Billing Agents</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ $agentId === $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                    @endforeach
                </select>
                <select name="month" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
                    <option value="">All Months</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                    @endfor
                </select>
                <select name="year" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
                    <option value="">All Years</option>
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
            <a href="{{ route('admin.reports.index', ['month' => $month ?: now()->month, 'year' => $year ?: now()->year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                📊 Platform Overview
            </a>
            <a href="{{ route('admin.reports.status_tag', ['month' => $month ?: now()->month, 'year' => $year ?: now()->year, 'agent_id' => $agentId]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                🏷️ Status & Tag Distribution
            </a>
            <a href="{{ route('admin.reports.quota', ['month' => $month ?: now()->month, 'year' => $year ?: now()->year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                💳 Quota & Overage Leaderboard
            </a>
            <a href="{{ route('admin.reports.flagged', ['month' => $month, 'year' => $year, 'agent_id' => $agentId]) }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-lg shadow-indigo-600/30">
                ⚠️ Consecutive Estimates
            </a>
        </div>

        <!-- Table -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-3">Billing Agent</th>
                            <th class="py-3 px-3">CA Number</th>
                            <th class="py-3 px-3">Consumer Name</th>
                            <th class="py-3 px-3">MRU Workspace</th>
                            <th class="py-3 px-3 text-center">Consecutive Count</th>
                            <th class="py-3 px-3 text-center">Basis</th>
                            <th class="py-3 px-3 text-center">Period</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($flagged as $item)
                            <tr class="hover:bg-slate-800/20 transition">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-white">{{ $item->user?->name ?? '—' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $item->user?->email }}</div>
                                </td>
                                <td class="py-3 px-3 font-mono font-bold text-cyan-400">
                                    {{ $item->ca_number }}
                                </td>
                                <td class="py-3 px-3 font-medium text-white">
                                    {{ $item->consumerAccount?->consumer_name ?? '—' }}
                                </td>
                                <td class="py-3 px-3 text-slate-400 font-mono text-[11px]">
                                    {{ $item->mru ? $item->mru->code : 'GENERAL' }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                        ⚠️ {{ $item->consecutive_count }}x LK/MD
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center font-mono font-bold">
                                    <span class="px-2 py-0.5 rounded text-[11px] {{ $item->billing_basis === 'MD' ? 'bg-rose-500/20 text-rose-300' : 'bg-amber-500/20 text-amber-300' }}">
                                        {{ $item->billing_basis }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center font-mono text-slate-400 text-[11px]">
                                    {{ $item->billing_month }}/{{ $item->billing_year }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-500">
                                    No consecutive estimate alerts found for the selected filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($flagged->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $flagged->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
