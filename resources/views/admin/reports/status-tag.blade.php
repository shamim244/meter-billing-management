<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>🏷️</span> Status & Tag Distribution
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    System-wide and per-Agent review status distribution and active tag breakdown.
                </p>
            </div>

            <!-- Month & Agent Filter -->
            <form method="GET" action="{{ route('admin.reports.status_tag') }}" class="flex flex-wrap items-center gap-2">
                <select name="agent_id" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
                    <option value="">All Billing Agents</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" {{ $agentId === $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                    @endforeach
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
            <a href="{{ route('admin.reports.status_tag', ['month' => $month, 'year' => $year, 'agent_id' => $agentId]) }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-lg shadow-indigo-600/30">
                🏷️ Status & Tag Distribution
            </a>
            <a href="{{ route('admin.reports.quota', ['month' => $month, 'year' => $year]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                💳 Quota & Overage Leaderboard
            </a>
            <a href="{{ route('admin.reports.flagged', ['month' => $month, 'year' => $year, 'agent_id' => $agentId]) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                ⚠️ Consecutive Estimates
            </a>
        </div>

        <!-- Distribution Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Review Status Card -->
            <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-4">
                <div class="border-b border-slate-800 pb-3">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Review Status Counts</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Total Bills: {{ number_format($statusBreakdown['total']) }}</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3 bg-slate-950 rounded-xl text-center border border-emerald-500/30">
                        <div class="text-[10px] font-bold text-emerald-400 uppercase">Submitted</div>
                        <div class="text-xl font-black text-emerald-300 font-mono mt-1">{{ number_format($statusBreakdown['submitted']) }}</div>
                    </div>
                    <div class="p-3 bg-slate-950 rounded-xl text-center border border-rose-500/30">
                        <div class="text-[10px] font-bold text-rose-400 uppercase">Critical</div>
                        <div class="text-xl font-black text-rose-300 font-mono mt-1">{{ number_format($statusBreakdown['critical']) }}</div>
                    </div>
                    <div class="p-3 bg-slate-950 rounded-xl text-center border border-amber-500/30">
                        <div class="text-[10px] font-bold text-amber-400 uppercase">Doubt</div>
                        <div class="text-xl font-black text-amber-300 font-mono mt-1">{{ number_format($statusBreakdown['doubt']) }}</div>
                    </div>
                    <div class="p-3 bg-slate-950 rounded-xl text-center border border-slate-700">
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Pending</div>
                        <div class="text-xl font-black text-slate-300 font-mono mt-1">{{ number_format($statusBreakdown['pending']) }}</div>
                    </div>
                </div>
            </div>

            <!-- Tag Breakdown Card -->
            <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 shadow-xl space-y-4">
                <div class="border-b border-slate-800 pb-3">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Active & Historical Tags</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Total Tagged Bills: {{ number_format($tagBreakdown['total_bills']) }}</p>
                </div>

                <div class="space-y-3">
                    @foreach($tagBreakdown['tags'] as $tag)
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-{{ $tag['color'] }}-500"></span>
                                <span class="font-semibold text-white">{{ $tag['short_label'] }}</span>
                                <span class="text-[10px] text-slate-400">({{ $tag['label'] }})</span>
                            </div>
                            <div class="flex items-center gap-3 font-mono">
                                <span class="font-bold text-white">{{ number_format($tag['count']) }}</span>
                                <span class="text-[10px] text-slate-400 w-12 text-right">{{ $tag['percentage'] }}%</span>
                            </div>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-{{ $tag['color'] }}-500 transition-all duration-500" style="width: {{ $tag['percentage'] }}%"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
