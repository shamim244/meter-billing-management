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
                    <span class="text-slate-500 block text-[10px]" x-text="'₹ ' + Number(diagResult.extraction.energy_charges || 0).toFixed(2) + ' / ₹ ' + Number(diagResult.extraction.fixed_charges || 0).toFixed(2)"></span>
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
