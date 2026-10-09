<!-- Billing Cycle PDF Cleanup & Storage Recovery -->
@if(!empty($cycleStats))
    <div class="bg-gradient-to-br from-white via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200/60 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20 flex items-center justify-center text-xl font-bold shrink-0">
                    🧹
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">Billing Cycle PDF Cleanup (Ledger Protected)</h3>
                    <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                        Delete physical PDF files from completed cycles to free up storage. <strong>Consumer readings, units, dues & remarks remain 100% preserved in database.</strong>
                    </p>
                </div>
            </div>

            <!-- Delete All Older Cycles Button -->
            <button type="button" 
                    @click="openDeleteOlderModal()"
                    :disabled="actionRunning"
                    class="w-full sm:w-auto px-4 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 text-xs font-bold transition flex items-center justify-center gap-1.5 shrink-0">
                <span>⚡</span>
                <span>Delete All Previous Months' PDFs</span>
            </button>
        </div>

        <!-- Cycles Breakdown Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($cycleStats as $cs)
                <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border {{ $cs['is_current'] ? 'border-brand-500/40 shadow-xs' : 'border-slate-200/70 dark:border-slate-800/80' }} flex flex-col justify-between space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-black text-slate-900 dark:text-white">{{ $cs['label'] }}</span>
                            @if($cs['is_current'])
                                <span class="ml-1.5 px-2 py-0.5 rounded-text-[10px] font-bold bg-brand-100 dark:bg-brand-950 text-brand-600 dark:text-cyan-400 border border-brand-200 dark:border-brand-800">
                                    Active Cycle
                                </span>
                            @endif
                        </div>
                        <span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">{{ $cs['total_size_formatted'] }}</span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-500 font-mono">
                        <span>{{ $cs['pdf_count'] }} PDFs on disk</span>
                        <span>{{ $cs['total_bills'] }} total CAs</span>
                    </div>

                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                        <span class="text-[10px] text-slate-400">Ledger Preserved</span>

                        @if($cs['pdf_count'] > 0)
                            <button type="button" 
                                    @click="openDeleteModal({{ $cs['month'] }}, {{ $cs['year'] }}, '{{ $cs['label'] }}', '{{ $cs['total_size_formatted'] }}', {{ $cs['pdf_count'] }}, {{ $cs['total_bills'] }})"
                                    :disabled="actionRunning"
                                    class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900 text-rose-600 dark:text-rose-300 text-xs font-bold border border-rose-200 dark:border-rose-800/80 transition flex items-center gap-1"
                                    title="Delete PDF files from disk while preserving ledger readings and amounts">
                                <span>🗑️</span>
                                <span>Delete PDFs</span>
                            </button>
                        @else
                            <span class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 font-semibold">Clean (0 B)</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
