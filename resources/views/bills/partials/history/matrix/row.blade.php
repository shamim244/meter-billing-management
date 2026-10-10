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
