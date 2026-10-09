{{-- Modal: New Billing Cycle (From Processing Center) --}}
<div x-show="showCycleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="if(!cycleInProgress) showCycleModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                    ⚡
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">New Billing Cycle</h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Initialize cycle session or download bills</p>
                </div>
            </div>
            <button @click="showCycleModal = false" :disabled="cycleInProgress" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 disabled:opacity-40 p-1">✕</button>
        </div>

        <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
            <!-- For MRU Select -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Target MRU Workspace</label>
                <select x-model="modalMruId" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                    @foreach($mrus as $m)
                        <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }}) — {{ $m->consumer_accounts_count }} consumers</option>
                    @endforeach
                </select>
            </div>

            <!-- Billing Month & Year Selectors -->
            <div class="grid grid-cols-2 gap-3">
                <!-- Billing Month -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Billing Month</label>
                    <select x-model="modalMonth" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5">May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>

                <!-- Billing Year -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Billing Year</label>
                    <select x-model="modalYear" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                        @php
                            $currYear = (int) date('Y');
                            $availableYears = range(max(2020, $currYear - 3), $currYear + 5);
                            rsort($availableYears);
                        @endphp
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Tip Box -->
            <div class="p-3.5 bg-blue-50/60 dark:bg-blue-950/40 rounded-2xl border border-blue-100 dark:border-blue-900/60 text-[11px] text-blue-800 dark:text-cyan-300 leading-relaxed">
                💡 <strong>Tip:</strong> Choose <strong>"Create Cycle Only"</strong> to initialize the billing session workspace immediately with preceding readings, or <strong>"Create & Download All"</strong> to fetch official PDFs right away.
            </div>

            <!-- Live Progress Box -->
            <div x-show="cycleInProgress" class="bg-slate-950 text-cyan-300 p-4 rounded-2xl font-mono text-xs space-y-1.5 border border-slate-800 shadow-inner">
                <div class="flex items-center gap-2 text-white font-bold">
                    <svg class="animate-spin h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Launching cycle & processing consumers...</span>
                </div>
                <div class="text-slate-400 text-[10px]">Processing consumers concurrently. Please wait...</div>
            </div>

            <!-- Result notification -->
            <div x-show="cycleResult" class="p-3.5 rounded-2xl text-xs font-semibold" :class="cycleResult?.success ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:rose-800'" x-text="cycleResult?.message"></div>
        </div>

        <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2.5">
            <button type="button" @click="showCycleModal = false" :disabled="cycleInProgress" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-center">
                Cancel
            </button>

            <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                <button type="button" @click="launchBillingCycle('create_only')" :disabled="cycleInProgress || !modalMruId" class="w-full sm:w-auto px-4 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 disabled:opacity-40 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold transition text-center">
                    ➕ Create Cycle Only
                </button>
                <button type="button" @click="launchBillingCycle('download_all')" :disabled="cycleInProgress || !modalMruId" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1">
                    <span>⚡</span> Create & Download All
                </button>
            </div>
        </div>
    </div>
</div>
