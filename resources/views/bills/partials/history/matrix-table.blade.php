{{-- 📊 Dedicated Monthly Meter Reading History (2D Matrix: Official PDF vs Field Working Reading) --}}
<div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📊</span> Dedicated Monthly Meter Reading History (2D Matrix)
                </h2>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    Dual-Source History
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Direct side-by-side comparison of Official NBPDCL PDF bill extractions vs Operator field entries. Working readings take calculation precedence for monthly averages.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <div class="px-3.5 py-1.5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/60 text-center">
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 block">Smart Avg Basis</span>
                <span class="text-sm font-black text-indigo-700 dark:text-indigo-300 font-mono">{{ $meterMatrix['average_units'] ?? 50 }} kWh</span>
            </div>
            <div class="px-3.5 py-1.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-center">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Recorded Periods</span>
                <span class="text-sm font-black text-slate-800 dark:text-slate-200 font-mono">{{ $meterMatrix['periods_count'] ?? 0 }}</span>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
            <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400">
                <tr>
                    <th class="py-3.5 px-4">Billing Month</th>
                    <th class="py-3.5 px-4 text-center">Official PDF Reading</th>
                    <th class="py-3.5 px-4 text-center">PDF Units</th>
                    <th class="py-3.5 px-4 text-center">Field Working Reading</th>
                    <th class="py-3.5 px-4 text-center">Working Units</th>
                    <th class="py-3.5 px-4 text-center">Smart Avg Units Used</th>
                    <th class="py-3.5 px-4 text-center">Basis</th>
                    <th class="py-3.5 px-4 text-center">Cycle Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                @forelse($meterMatrix['periods'] ?? [] as $period)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition">
                        <td class="py-3 px-4 font-bold text-slate-900 dark:text-white font-mono text-xs">
                            {{ $period['month_name'] }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-xs">
                            @if($period['has_pdf'])
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $period['pdf_reading'] ?? '—' }}</span>
                                @if($period['pdf_previous'])
                                    <span class="text-[10px] text-slate-400 block">Prev: {{ $period['pdf_previous'] }}</span>
                                @endif
                            @else
                                <span class="text-slate-400 italic">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-xs">
                            @if($period['pdf_units'] !== null)
                                <span class="font-bold text-slate-700 dark:text-slate-300">{{ $period['pdf_units'] }} kWh</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-xs">
                            @if($period['has_working'])
                                <span class="font-black text-blue-600 dark:text-cyan-400 bg-blue-50/60 dark:bg-blue-950/60 px-2 py-0.5 rounded-lg border border-blue-100 dark:border-blue-900/60">
                                    {{ $period['working_reading'] }}
                                </span>
                            @else
                                <span class="text-slate-400 italic">Not entered</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-xs">
                            @if($period['working_units'] !== null)
                                <span class="font-bold text-blue-600 dark:text-cyan-400">{{ $period['working_units'] }} kWh</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-xs">
                            <span class="font-black px-2 py-0.5 rounded text-xs {{ $period['has_working'] ? 'bg-indigo-100 dark:bg-indigo-950/80 text-indigo-800 dark:text-indigo-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                                {{ $period['effective_units'] }} kWh
                            </span>
                            @if(!empty($period['delta_formula']))
                                <span class="text-[9px] text-slate-500 font-mono block">{{ $period['delta_formula'] }}</span>
                            @endif
                            <span class="text-[9px] text-slate-400 block">Source: {{ $period['has_working'] ? 'Worker (Priority)' : 'PDF Bill' }}</span>
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-xs">
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase
                                @if($period['billing_basis'] === 'OK') bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300
                                @elseif($period['billing_basis'] === 'LK') bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300
                                @elseif($period['billing_basis'] === 'MD') bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300
                                @else bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 @endif">
                                {{ $period['billing_basis'] ?: 'OK' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($period['is_closed'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300">
                                    <span>🔒</span> Closed (Submitted)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-cyan-300">
                                    <span>📝</span> Active Cycle
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-slate-400 dark:text-slate-600 text-xs">
                            No dual-source meter readings recorded yet. Once bills are parsed or working readings are entered, they will appear here.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
