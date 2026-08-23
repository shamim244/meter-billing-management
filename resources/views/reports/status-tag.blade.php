<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Header & Export Action -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('reports.usage', ['month' => $month, 'year' => $year]) }}" class="text-xs font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                        ← ROI Overview
                    </a>
                </div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5 mt-1">
                    <span>🏷️</span> Monthly Status & Tag Report
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Detailed review status and tag distribution with drill-down consumer ledger records.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('reports.status_tag.export_csv', ['month' => $month, 'year' => $year, 'mru_id' => $mruId, 'status' => $status, 'tag' => $tag]) }}" 
                   class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-emerald-600/20">
                    <span>📄</span> Export CSV Report
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <form method="GET" action="{{ route('reports.status_tag') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                <!-- Month -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Month</label>
                    <select name="month" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Year -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Year</label>
                    <select name="year" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                        @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <!-- MRU Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">MRU Workspace</label>
                    <select name="mru_id" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                        <option value="">All MRUs</option>
                        @foreach($mrus as $mru)
                            <option value="{{ $mru->id }}" {{ $mruId === $mru->id ? 'selected' : '' }}>{{ $mru->code }} - {{ $mru->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Review Status</label>
                    <select name="status" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                        <option value="all">All Statuses</option>
                        <option value="submitted" {{ $status === 'submitted' ? 'selected' : '' }}>✅ Submitted</option>
                        <option value="critical" {{ $status === 'critical' ? 'selected' : '' }}>❌ Critical</option>
                        <option value="doubt" {{ $status === 'doubt' ? 'selected' : '' }}>⚠️ Doubt</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>⏳ Pending</option>
                    </select>
                </div>

                <!-- Tag Filter -->
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tag</label>
                        <select name="tag" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                            <option value="all">All Tags</option>
                            @foreach($tagBreakdown['tags'] as $t)
                                <option value="{{ $t['code'] }}" {{ strtoupper($tag) === strtoupper($t['code']) ? 'selected' : '' }}>
                                    🏷️ {{ $t['short_label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Distribution Bars -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Review Status Cards -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                <div class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                    Status Counts (Total: {{ number_format($statusBreakdown['total']) }})
                </div>
                <div class="grid grid-cols-4 gap-2">
                    <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl text-center border border-emerald-200/60 dark:border-emerald-800/40">
                        <div class="text-[9px] font-bold text-emerald-600 uppercase">Submitted</div>
                        <div class="text-lg font-black text-emerald-700 dark:text-emerald-300 font-mono">{{ $statusBreakdown['submitted'] }}</div>
                    </div>
                    <div class="p-2.5 bg-rose-50 dark:bg-rose-950/40 rounded-xl text-center border border-rose-200/60 dark:border-rose-800/40">
                        <div class="text-[9px] font-bold text-rose-600 uppercase">Critical</div>
                        <div class="text-lg font-black text-rose-700 dark:text-rose-300 font-mono">{{ $statusBreakdown['critical'] }}</div>
                    </div>
                    <div class="p-2.5 bg-amber-50 dark:bg-amber-950/40 rounded-xl text-center border border-amber-200/60 dark:border-amber-800/40">
                        <div class="text-[9px] font-bold text-amber-600 uppercase">Doubt</div>
                        <div class="text-lg font-black text-amber-700 dark:text-amber-300 font-mono">{{ $statusBreakdown['doubt'] }}</div>
                    </div>
                    <div class="p-2.5 bg-slate-100 dark:bg-slate-800 rounded-xl text-center border border-slate-200 dark:border-slate-700">
                        <div class="text-[9px] font-bold text-slate-600 uppercase">Pending</div>
                        <div class="text-lg font-black text-slate-700 dark:text-slate-300 font-mono">{{ $statusBreakdown['pending'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Tags Counts -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                <div class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                    Tag Breakdown (Total: {{ number_format($tagBreakdown['total_bills']) }})
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($tagBreakdown['tags'] as $t)
                        <div class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-{{ $t['color'] }}-500"></span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ $t['short_label'] }}:</span>
                            <span class="text-xs font-bold font-mono text-slate-900 dark:text-white">{{ $t['count'] }}</span>
                            <span class="text-[10px] text-slate-400">({{ $t['percentage'] }}%)</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Drill-Down Consumer Table -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Matching Consumers ({{ $consumers->total() }})
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-3">CA Number</th>
                            <th class="py-3 px-3">Consumer Name</th>
                            <th class="py-3 px-3">MRU</th>
                            <th class="py-3 px-3 text-center">Basis</th>
                            <th class="py-3 px-3 text-right">Units</th>
                            <th class="py-3 px-3 text-right">Amount (₹)</th>
                            <th class="py-3 px-3 text-center">Review Status</th>
                            <th class="py-3 px-3 text-center">Tag</th>
                            <th class="py-3 px-3">Remark</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                        @forelse($consumers as $bill)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-2.5 px-3 font-mono font-bold text-blue-600 dark:text-cyan-400">
                                    {{ $bill->ca_number }}
                                </td>
                                <td class="py-2.5 px-3 font-medium text-slate-900 dark:text-white">
                                    {{ $bill->consumer_name }}
                                </td>
                                <td class="py-2.5 px-3 text-slate-500 font-mono text-[11px]">
                                    {{ $bill->mru ? $bill->mru->code : 'GENERAL' }}
                                </td>
                                <td class="py-2.5 px-3 text-center font-mono font-bold text-[11px]">
                                    <span class="px-1.5 py-0.5 rounded {{ $bill->billing_basis === 'OK' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                        {{ $bill->billing_basis ?: 'OK' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-semibold">
                                    {{ number_format($bill->units_consumed ?? 0) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    ₹{{ number_format($bill->total_amount, 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    @php
                                        $st = $bill->resolved_status ?? 'pending';
                                        $statusClass = match($st) {
                                            'submitted' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                                            'critical' => 'bg-rose-500/20 text-rose-400 border-rose-500/30',
                                            'doubt' => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                                            default => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border {{ $statusClass }}">
                                        {{ $st }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ $bill->resolved_tag ?? ($bill->tag ?: 'OK') }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-slate-500 text-[11px] max-w-[200px] truncate">
                                    {{ $bill->resolved_remark ?? ($bill->remark ?: '—') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400">
                                    No bills match the selected month, status, and tag filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($consumers->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $consumers->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
