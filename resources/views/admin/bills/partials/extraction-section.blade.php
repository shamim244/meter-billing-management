<!-- Section 2: Dual Extraction Engine Strategy -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
        <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>🧠</span>
                <span>Extraction Engine Strategy</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
                Algorithmically detects whether a PDF is in modern JasperReports Unicode format or legacy Kruti-Dev format.
            </p>
        </div>
        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
            Active: {{ strtoupper($settings['extraction_engine']) }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Option 1: Auto Detection (Recommended) -->
        <label class="relative flex flex-col p-5 rounded-2xl border cursor-pointer transition {{ $settings['extraction_engine'] === 'auto' ? 'bg-indigo-950/20 border-indigo-500 ring-2 ring-indigo-500/20' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <input type="radio" name="extraction_engine" value="auto" class="text-indigo-600 focus:ring-indigo-500" {{ $settings['extraction_engine'] === 'auto' ? 'checked' : '' }}>
                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                    Recommended
                </span>
            </div>
            <span class="font-bold text-sm text-white mt-3">Signature Auto-Detect</span>
            <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                Inspects internal PDF markers and font signatures. Dynamically routes modern bills to <strong>JasperUnicodeExtractor</strong> and legacy bills to <strong>LegacyKrutiDevExtractor</strong>, with automatic cross-fallback if key fields are missing.
            </p>
        </label>

        <!-- Option 2: Forced Jasper Unicode -->
        <label class="relative flex flex-col p-5 rounded-2xl border cursor-pointer transition {{ $settings['extraction_engine'] === 'jasper_unicode' ? 'bg-indigo-950/20 border-indigo-500 ring-2 ring-indigo-500/20' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <input type="radio" name="extraction_engine" value="jasper_unicode" class="text-indigo-600 focus:ring-indigo-500" {{ $settings['extraction_engine'] === 'jasper_unicode' ? 'checked' : '' }}>
                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-full bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                    2-Page Unicode
                </span>
            </div>
            <span class="font-bold text-sm text-white mt-3">Jasper Unicode Extractor</span>
            <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                Optimized for the new 2026+ 2-page JasperReports layout. Extracts Hindi & English consumer identity, meter readings, dates, amount payable, and the 12-month consumption table ledger.
            </p>
        </label>

        <!-- Option 3: Forced Legacy KrutiDev -->
        <label class="relative flex flex-col p-5 rounded-2xl border cursor-pointer transition {{ $settings['extraction_engine'] === 'legacy_krutidev' ? 'bg-indigo-950/20 border-indigo-500 ring-2 ring-indigo-500/20' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <input type="radio" name="extraction_engine" value="legacy_krutidev" class="text-indigo-600 focus:ring-indigo-500" {{ $settings['extraction_engine'] === 'legacy_krutidev' ? 'checked' : '' }}>
                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">
                    1-Page Kruti-Dev
                </span>
            </div>
            <span class="font-bold text-sm text-white mt-3">Legacy Kruti-Dev Extractor</span>
            <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                Optimized for older 1-page iText bills generated prior to May 2026 using Kruti-Dev 010 encoded text streams (e.g. <code>miHkks</code>, <code>fcy ekg</code>, <code>rd ns; jkf'k</code>).
            </p>
        </label>
    </div>
</div>
