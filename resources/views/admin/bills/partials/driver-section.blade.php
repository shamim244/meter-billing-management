<!-- Section 1: Dual Download Strategy -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
        <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>📡</span>
                <span>Download Driver Strategy</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
                Select how bill PDFs are fetched from NBPDCL servers. If new WSS faces issues, switch to Legacy or use Auto.
            </p>
        </div>
        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
            Active: {{ strtoupper($settings['download_driver']) }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Option 1: Auto Fallback (Recommended) -->
        <label class="relative flex flex-col p-5 rounded-2xl border cursor-pointer transition {{ $settings['download_driver'] === 'auto' ? 'bg-indigo-950/20 border-indigo-500 ring-2 ring-indigo-500/20' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <input type="radio" name="download_driver" value="auto" class="text-indigo-600 focus:ring-indigo-500" {{ $settings['download_driver'] === 'auto' ? 'checked' : '' }}>
                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                    Recommended
                </span>
            </div>
            <span class="font-bold text-sm text-white mt-3">Smart Auto-Fallback</span>
            <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                Sends high-speed encrypted requests to the modern <strong>WSS FluentGrid API</strong> first. If WSS fails or returns empty for any CA, it instantly and transparently falls back to the <strong>Legacy BSPHCL ASMX API</strong>.
            </p>
        </label>

        <!-- Option 2: Forced WSS -->
        <label class="relative flex flex-col p-5 rounded-2xl border cursor-pointer transition {{ $settings['download_driver'] === 'wss' ? 'bg-indigo-950/20 border-indigo-500 ring-2 ring-indigo-500/20' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <input type="radio" name="download_driver" value="wss" class="text-indigo-600 focus:ring-indigo-500" {{ $settings['download_driver'] === 'wss' ? 'checked' : '' }}>
                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-full bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                    2026+ Live
                </span>
            </div>
            <span class="font-bold text-sm text-white mt-3">WSS FluentGrid Only</span>
            <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                Forces all downloads through the modern encrypted <code>wss.nbpdcl.co.in</code> bridge service. Delivers 2-page Unicode JasperReports PDFs for Postpaid and Smart Prepaid consumers.
            </p>
        </label>

        <!-- Option 3: Forced Legacy -->
        <label class="relative flex flex-col p-5 rounded-2xl border cursor-pointer transition {{ $settings['download_driver'] === 'legacy' ? 'bg-indigo-950/20 border-indigo-500 ring-2 ring-indigo-500/20' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <input type="radio" name="download_driver" value="legacy" class="text-indigo-600 focus:ring-indigo-500" {{ $settings['download_driver'] === 'legacy' ? 'checked' : '' }}>
                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">
                    Legacy Fallback
                </span>
            </div>
            <span class="font-bold text-sm text-white mt-3">Legacy BSPHCL ASMX Only</span>
            <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                Forces all downloads through the original <code>api.bsphcl.co.in/nbWSMobileApp/ViewBill.asmx</code> endpoint. Use this emergency switch if WSS is experiencing upstream maintenance.
            </p>
        </label>
    </div>
</div>
