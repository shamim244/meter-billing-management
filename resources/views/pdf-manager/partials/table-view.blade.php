<!-- Data Table View -->
<div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
            <thead class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-[11px] uppercase font-bold text-slate-500 tracking-wider">
                <tr>
                    <th class="py-3.5 px-4 w-10 text-center">
                        <input type="checkbox" @change="toggleSelectAll($event)" :checked="isAllSelected()" class="rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500">
                    </th>
                    <th class="py-3.5 px-4">CA Number / Consumer</th>
                    <th class="py-3.5 px-4">MRU Area</th>
                    <th class="py-3.5 px-4 text-center">Period</th>
                    <th class="py-3.5 px-4 text-center">File Size</th>
                    <th class="py-3.5 px-4 text-center">Extraction</th>
                    <th class="py-3.5 px-4 text-right">Amount</th>
                    <th class="py-3.5 px-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                @forelse($bills as $bill)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="py-3 px-4 text-center">
                            <input type="checkbox" 
                                   value="{{ $bill->id }}" 
                                   x-model="selectedIds" 
                                   class="rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500">
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-mono font-bold text-brand-600 dark:text-brand-400 text-xs sm:text-sm">
                                {{ $bill->ca_number }}
                            </div>
                            <div class="text-xs font-semibold text-slate-900 dark:text-white truncate max-w-[180px]">
                                {{ $bill->consumer_name ?: '—' }}
                            </div>
                            @if($bill->meter_no)
                                <div class="text-[10px] text-slate-500 font-mono">Meter: {{ $bill->meter_no }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-0.5 rounded-lg text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 text-cyan-600 dark:text-cyan-400 border border-slate-200 dark:border-slate-700">
                                {{ $bill->mru ? $bill->mru->code : 'GENERAL' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-xs font-bold text-slate-600 dark:text-slate-400">
                            {{ sprintf('%02d/%04d', $bill->billing_month, $bill->billing_year) }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-xs">
                            @if($bill->file_exists)
                                <span class="px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/80 font-bold">
                                    {{ $bill->file_size_formatted }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/80 font-bold text-[10px]">
                                    Missing
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($bill->parse_status === 'parsed')
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                    ⚡ Parsed ({{ $bill->units_consumed }} kWh)
                                </span>
                            @elseif($bill->parse_status === 'failed')
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-800" title="{{ $bill->error_message }}">
                                    ❌ Parse Failed
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">
                                    Unparsed
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-black font-mono text-slate-900 dark:text-white">
                            ₹{{ number_format($bill->total_amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                @if($bill->file_exists)
                                    <a href="{{ route('bills.pdf', $bill) }}" target="_blank" class="p-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/60 dark:hover:bg-brand-900 text-brand-600 dark:text-brand-300 transition" title="Preview PDF in tab">
                                        👁️
                                    </a>
                                    <button type="button" @click="reparseSingle({{ $bill->id }})" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900 text-emerald-600 dark:text-emerald-300 transition" title="Re-parse PDF Data">
                                        ⚡
                                    </button>
                                    <button type="button" @click="deleteSingle({{ $bill->id }}, '{{ $bill->ca_number }}')" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900 text-rose-600 dark:text-rose-300 transition" title="Delete PDF">
                                        🗑️
                                    </button>
                                @else
                                    <button type="button" 
                                            @click="redownloadSingle({{ $bill->id }})" 
                                            :disabled="actionRunning || loadingBillId === {{ $bill->id }}"
                                            class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 dark:hover:bg-indigo-900 disabled:opacity-50 text-indigo-600 dark:text-indigo-300 text-xs font-bold transition flex items-center gap-1">
                                        <span x-show="loadingBillId !== {{ $bill->id }}">⬇️ Fetch</span>
                                        <span x-show="loadingBillId === {{ $bill->id }}" class="inline-flex items-center gap-1 text-[11px]">
                                            <span class="animate-spin text-[10px]">⏳</span> Fetching...
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs sm:text-sm">
                            No electricity bill PDFs found matching the selected filter criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
