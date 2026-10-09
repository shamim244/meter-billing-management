<!-- Billing & Audit Activity -->
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h2 class="text-base font-bold text-white flex items-center gap-2">
            <span>⚡</span> Bill Review & Audit Activity
        </h2>
        <span class="text-xs text-slate-400 font-mono">Lifetime Total: {{ number_format($billStats['total']) }}</span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-slate-900/70 p-4 rounded-2xl border border-slate-800 text-center">
            <div class="text-[10px] font-bold text-slate-400 uppercase">Submitted / OK</div>
            <div class="text-xl font-black text-emerald-400 font-mono mt-1">
                {{ number_format($billStats['submitted']) }}
            </div>
        </div>

        <div class="bg-slate-900/70 p-4 rounded-2xl border border-slate-800 text-center">
            <div class="text-[10px] font-bold text-slate-400 uppercase">Doubt / Re-check</div>
            <div class="text-xl font-black text-amber-400 font-mono mt-1">
                {{ number_format($billStats['doubt']) }}
            </div>
        </div>

        <div class="bg-slate-900/70 p-4 rounded-2xl border border-slate-800 text-center">
            <div class="text-[10px] font-bold text-slate-400 uppercase">Critical / Issue</div>
            <div class="text-xl font-black text-rose-400 font-mono mt-1">
                {{ number_format($billStats['critical']) }}
            </div>
        </div>

        <div class="bg-slate-900/70 p-4 rounded-2xl border border-slate-800 text-center">
            <div class="text-[10px] font-bold text-slate-400 uppercase">Downloaded PDFs</div>
            <div class="text-xl font-black text-indigo-400 font-mono mt-1">
                {{ number_format($billStats['downloaded_pdfs']) }}
            </div>
        </div>
    </div>
</div>
