<!-- Card / Grid View Mode -->
<div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" x-cloak>
    @forelse($bills as $bill)
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-4 hover:border-brand-500/50 transition">
            <div>
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 text-cyan-600 dark:text-cyan-400">
                        {{ $bill->mru ? $bill->mru->code : 'GENERAL' }}
                    </span>
                    <input type="checkbox" value="{{ $bill->id }}" x-model="selectedIds" class="rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500">
                </div>

                <div class="mt-3 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-brand-400 flex items-center justify-center text-xl font-bold shrink-0">
                        📄
                    </div>
                    <div class="truncate">
                        <div class="font-mono font-bold text-brand-600 dark:text-brand-400 text-sm">
                            {{ $bill->ca_number }}
                        </div>
                        <div class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                            {{ $bill->consumer_name ?: 'Consumer ' . $bill->ca_number }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-2 text-xs font-mono">
                    <div>
                        <span class="text-[10px] text-slate-400 block uppercase">Period</span>
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ sprintf('%02d/%04d', $bill->billing_month, $bill->billing_year) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 block uppercase">Amount</span>
                        <span class="font-bold text-slate-900 dark:text-white">₹{{ number_format($bill->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <span class="text-[11px] font-mono font-bold {{ $bill->file_exists ? 'text-emerald-500' : 'text-rose-500' }}">
                    {{ $bill->file_exists ? $bill->file_size_formatted : 'Missing' }}
                </span>

                <div class="flex items-center gap-1.5">
                    @if($bill->file_exists)
                        <a href="{{ route('bills.pdf', $bill) }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-brand-50 hover:bg-brand-100 dark:bg-brand-950 dark:hover:bg-brand-900 text-brand-600 dark:text-brand-300 text-xs font-bold transition">
                            View
                        </a>
                    @endif
                    <button type="button" @click="deleteSingle({{ $bill->id }}, '{{ $bill->ca_number }}')" class="p-1 rounded-lg text-slate-400 hover:text-rose-500 text-xs">
                        🗑️
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
            No bill documents found in this view.
        </div>
    @endforelse
</div>
