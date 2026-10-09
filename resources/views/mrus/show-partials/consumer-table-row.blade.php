<tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
    <td class="py-3.5 px-6 font-mono font-bold text-blue-600 dark:text-cyan-400">
        <div class="flex items-center gap-1.5 flex-wrap">
            <a href="{{ route('bills.history', $consumer->ca_number) }}" class="hover:underline" title="View historical ledger">
                {{ $consumer->ca_number }}
            </a>
        </div>
    </td>
    <td class="py-3.5 px-6 font-semibold text-slate-900 dark:text-white">
        {{ $consumer->consumer_name ?: '—' }}
    </td>
    <td class="py-3.5 px-4 text-center">
        <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200/50 dark:border-indigo-800/50">
            {{ $consumer->tariff_category ?: 'DS-II' }}
        </span>
    </td>
    <td class="py-3.5 px-4 text-center">
        @php
            $basis = strtoupper(trim((string)($consumer->billing_basis ?: 'OK')));
            $basisColor = match($basis) {
                'OK' => 'bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border-emerald-200/50 dark:border-emerald-800/50',
                'LK' => 'bg-amber-50 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border-amber-200/50 dark:border-amber-800/50',
                'MD' => 'bg-rose-50 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 border-rose-200/50 dark:border-rose-800/50',
                'PL' => 'bg-blue-50 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 border-blue-200/50 dark:border-blue-800/50',
                'RN' => 'bg-purple-50 dark:bg-purple-950/70 text-purple-700 dark:text-purple-300 border-purple-200/50 dark:border-purple-800/50',
                default => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
            };
        @endphp
        <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono border {{ $basisColor }}">
            {{ $basis }}
        </span>
    </td>
    <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-800 dark:text-slate-200">
        ₹{{ number_format((float)($consumer->baseline_amount ?: 0), 2) }}
    </td>
    <td class="py-3.5 px-4 text-center font-mono font-semibold text-slate-700 dark:text-slate-300">
        {{ $consumer->baseline_previous_reading !== null ? $consumer->baseline_previous_reading : ($consumer->last_working_reading !== null ? $consumer->last_working_reading : '—') }}
    </td>
    <td class="py-3.5 px-4 text-center font-mono text-slate-500 dark:text-slate-400">
        {{ $consumer->meter_no ?: '—' }}
    </td>
    <td class="py-3.5 px-4 text-center font-mono text-slate-500 dark:text-slate-400">
        {{ $consumer->mobile ?: '—' }}
    </td>
    <td class="py-3.5 px-4 text-center">
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $consumer->status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
            <span class="w-1 h-1 rounded-full {{ $consumer->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
            {{ $consumer->status }}
        </span>
    </td>
    <td class="py-3.5 px-6 text-center">
        <div class="flex items-center justify-center gap-2">
            <button @click="openEditConsumerModal(@js($consumer))" class="text-blue-600 dark:text-cyan-400 hover:underline text-xs font-bold">
                Edit
            </button>
            <form method="POST" action="{{ route('mrus.consumers.destroy', [$mru, $consumer]) }}" onsubmit="return confirm('Remove consumer {{ $consumer->ca_number }} from master list?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-bold">
                    Delete
                </button>
            </form>
        </div>
    </td>
</tr>
