<!-- Section 7: Real-Time Diagnostic Sandbox -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <div class="border-b border-slate-800/80 pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>🧪</span>
                <span>Live Engine & Extraction Diagnostic Sandbox</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
                Test live download connectivity and PDF layout extraction in-flight without altering any database records.
            </p>
        </div>
        <span class="text-[11px] text-amber-400 bg-amber-500/10 px-3 py-1 rounded-xl border border-amber-500/20 font-medium">
            Read-Only Diagnostic Test
        </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Sample CA Number</label>
            <input type="text" x-model="diagCa" placeholder="e.g. 10230041576" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Test Driver</label>
            <select x-model="diagDriver" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
                <option value="auto">Auto (WSS with Legacy Fallback)</option>
                <option value="wss">WSS FluentGrid Only</option>
                <option value="legacy">Legacy BSPHCL ASMX Only</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Billing Month</label>
            <select x-model="diagMonth" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ (int)date('n') === $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endfor
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Billing Year</label>
            <input type="number" x-model="diagYear" value="{{ (int)date('Y') }}" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
        </div>
    </div>

    <div class="flex justify-end">
        <button type="button" @click="runDiagnostic()" :disabled="diagLoading" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-lg transition flex items-center gap-2">
            <svg x-show="diagLoading" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
            <span x-text="diagLoading ? 'Testing Live Endpoints...' : '🚀 Run Live Diagnostic'"></span>
        </button>
    </div>

    <!-- Diagnostic Results View -->
    <div x-show="diagResult" x-cloak class="mt-6 p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
            <span class="text-xs font-black uppercase tracking-wider text-slate-400">Diagnostic Result</span>
            <span :class="diagResult?.success ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border-rose-500/30'" class="px-2.5 py-0.5 text-xs font-bold rounded-full border" x-text="diagResult?.success ? 'SUCCESS (200 OK)' : 'FAILED'"></span>
        </div>

        <!-- Network Metrics Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
            <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800/80">
                <span class="text-slate-500 block text-[10px] font-bold uppercase">Driver Executed</span>
                <span class="text-white font-bold font-mono" x-text="diagResult?.driver_executed"></span>
            </div>
            <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800/80">
                <span class="text-slate-500 block text-[10px] font-bold uppercase">Latency</span>
                <span class="text-white font-bold font-mono" x-text="diagResult?.latency_ms + ' ms'"></span>
            </div>
            <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800/80">
                <span class="text-slate-500 block text-[10px] font-bold uppercase">Payload Size</span>
                <span class="text-white font-bold font-mono" x-text="Math.round(diagResult?.pdf_bytes / 1024) + ' KB'"></span>
            </div>
            <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800/80">
                <span class="text-slate-500 block text-[10px] font-bold uppercase">Baseline Cycle</span>
                <span class="font-bold font-mono" :class="diagResult?.lookback_steps > 0 ? 'text-amber-400' : 'text-emerald-400'" x-text="diagResult?.resolved_cycle ? (diagResult.resolved_cycle + (diagResult.lookback_steps > 0 ? ' (' + diagResult.lookback_steps + 'm back)' : ' (Exact)')) : 'N/A'"></span>
            </div>
            <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800/80">
                <span class="text-slate-500 block text-[10px] font-bold uppercase">Layout Detected</span>
                <span class="text-indigo-400 font-bold font-mono" x-text="diagResult?.extraction?.detected_format || 'N/A'"></span>
            </div>
        </div>

        <!-- Extracted Fields Table -->
        <template x-if="diagResult?.extraction && !diagResult?.extraction?.error">
            <div class="pt-3 border-t border-slate-800/80 space-y-2">
                <div class="flex items-center justify-between text-xs font-bold text-slate-300">
                    <span>Extracted Structured Data</span>
                    <span class="text-indigo-400 font-mono text-[11px]" x-text="'Engine: ' + diagResult?.extraction?.extractor_used"></span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 text-xs font-mono">
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">Consumer Name</span>
                        <span class="text-white font-sans font-bold truncate block" x-text="diagResult.extraction.consumer_name || '—'"></span>
                    </div>
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">Bill Month</span>
                        <span class="text-white truncate block" x-text="diagResult.extraction.bill_month || '—'"></span>
                    </div>
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">Bill Number</span>
                        <span class="text-white truncate block text-[10px]" x-text="diagResult.extraction.bill_number || '—'"></span>
                    </div>
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">Total Amount</span>
                        <span class="text-emerald-400 font-bold block" x-text="'₹ ' + Number(diagResult.extraction.total_amount).toFixed(2)"></span>
                    </div>
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">Current Reading</span>
                        <span class="text-white block" x-text="diagResult.extraction.current_reading ?? '—'"></span>
                    </div>
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">Units Consumed</span>
                        <span class="text-white block" x-text="diagResult.extraction.units_consumed ?? '0'"></span>
                    </div>
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">Sanctioned Load</span>
                        <span class="text-cyan-300 block" x-text="diagResult.extraction.sanctioned_load || '—'"></span>
                    </div>
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">Meter Number</span>
                        <span class="text-white block" x-text="diagResult.extraction.meter_no || '—'"></span>
                    </div>
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">Tariff Category</span>
                        <span class="text-amber-300 block" x-text="diagResult.extraction.tariff_category || '—'"></span>
                    </div>
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">Billing Basis</span>
                        <span class="text-emerald-300 block" x-text="diagResult.extraction.billing_basis || '—'"></span>
                    </div>
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">Energy / Demand Chg</span>
                        <span class="text-slate-300 block text-[10px]" x-text="'₹ ' + Number(diagResult.extraction.energy_charges || 0).toFixed(2) + ' / ₹ ' + Number(diagResult.extraction.fixed_charges || 0).toFixed(2)"></span>
                    </div>
                    <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                        <span class="text-slate-500 block text-[9px] uppercase">MRU Identifier</span>
                        <span class="text-purple-300 block" x-text="diagResult.extraction.mru || '—'"></span>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="diagResult?.error">
            <div class="p-3 bg-rose-950/60 rounded-xl border border-rose-800/80 text-xs text-rose-300" x-text="diagResult.error"></div>
        </template>
    </div>
</div>
