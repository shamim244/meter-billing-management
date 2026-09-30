<x-admin-layout>
    <x-slot name="header">
        NBPDCL Download & Extraction Engine
    </x-slot>

    <div class="space-y-8" x-data="engineSettingsManager()">
        <!-- Top Toolbar & Navigation -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl">
            <div>
                <h1 class="text-xl font-black text-white flex items-center gap-3">
                    <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-lg">⚡</span>
                    NBPDCL Billing & Extraction Engine
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Configure download drivers, automated fallbacks, and layout signature detection engines for NBPDCL consumer bills.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.bills.index') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 rounded-xl text-xs font-bold border border-slate-700/60 transition flex items-center gap-2">
                    <span>📑</span>
                    <span>All Bills Inspector</span>
                </a>
                <form action="{{ route('admin.bills.engine-settings.reset') }}" method="POST" onsubmit="return confirm('Reset all NBPDCL download & extraction engine configurations to factory defaults?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 rounded-xl text-xs font-bold border border-rose-800/40 transition">
                        Reset Defaults
                    </button>
                </form>
            </div>
        </div>

        @if(session('status'))
            <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-800/60 text-emerald-300 text-xs font-semibold flex items-center gap-3">
                <span class="text-base">✅</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-800/60 text-rose-300 text-xs font-semibold space-y-1">
                <div class="font-bold flex items-center gap-2">
                    <span>⚠️</span>
                    <span>Please correct the errors below:</span>
                </div>
                <ul class="list-disc list-inside pl-4 text-[11px] text-rose-400">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Settings Form -->
        <form action="{{ route('admin.bills.engine-settings.update') }}" method="POST" class="space-y-8">
            @csrf

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

            <!-- Section 3: Smart Average & Spike Filter Configuration -->
            <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
                <input type="hidden" name="has_spike_settings" value="1">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-800/80 pb-4 gap-2">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>📊</span>
                            <span>Smart Average & Spike Filter Configuration</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Dynamic dual-layer quality gate for historical consumption records, automated spike trimming, and category-level agricultural bypass protection.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="resetSpikeDefaults()" class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-700/60 transition" title="Reset multipliers and buffer to factory defaults in form">
                            🔄 Reset Form Presets
                        </button>
                        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg {{ $settings['calculation_filter_enabled'] ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700' }}">
                            {{ $settings['calculation_filter_enabled'] ? '🛡️ Filter: Active' : 'Pass-Through (Off)' }}
                        </span>
                    </div>
                </div>

                <!-- Dual Quality Gate Toggles (Layer 1 & Layer 2) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Layer 1: Ingestion-Time Quality Gate -->
                    <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs uppercase tracking-wider text-indigo-400 flex items-center gap-1.5">
                                <span>📥</span>
                                <span>Layer 1: Ingestion-Time Gate</span>
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="extraction_filter_enabled" value="1" class="sr-only peer" {{ $settings['extraction_filter_enabled'] ? 'checked' : '' }}>
                                <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                        <h3 class="text-sm font-bold text-white">Extraction Filter Gate</h3>
                        <p class="text-[11px] text-slate-400 leading-relaxed">
                            Applied during PDF bill parsing and historical table ingest. Eliminates ghost rows (0 units, null readings), marks non-OK bases as inactive for average consumption, and flags extreme spikes with <code>is_spike = true</code> metadata.
                        </p>
                    </div>

                    <!-- Layer 2: Calculation-Time Smart Average Gate -->
                    <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                                <span>⚡</span>
                                <span>Layer 2: Calculation-Time Gate</span>
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="calculation_filter_enabled" value="1" class="sr-only peer" {{ $settings['calculation_filter_enabled'] ? 'checked' : '' }}>
                                <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                        <h3 class="text-sm font-bold text-white">Smart Average Calculation Gate</h3>
                        <p class="text-[11px] text-slate-400 leading-relaxed">
                            Applied dynamically during live operator review, bulk projection, and cycle creation. Evaluates clean median from OK months, excludes non-OK bases and frozen cycles, and trims outlier spikes based on consumer tariff multipliers.
                        </p>
                    </div>
                </div>

                <!-- Exclusion Rules Toggles -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <label class="flex items-start gap-3 p-4 rounded-xl bg-slate-900/40 border border-slate-800 hover:border-slate-700 cursor-pointer transition">
                        <input type="checkbox" name="filter_non_ok_bases" value="1" class="mt-0.5 rounded border-slate-700 text-indigo-600 focus:ring-indigo-500 bg-slate-800" {{ $settings['filter_non_ok_bases'] ? 'checked' : '' }}>
                        <div>
                            <span class="text-xs font-bold text-white block">Exclude Non-OK Bases (MD, LK, PL, DL, EST)</span>
                            <span class="text-[11px] text-slate-400 mt-0.5 block leading-normal">
                                Prevents flat assessed MD or provisional LK months from contaminating normal average consumption.
                            </span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 rounded-xl bg-slate-900/40 border border-slate-800 hover:border-slate-700 cursor-pointer transition">
                        <input type="checkbox" name="filter_zero_unit_months" value="1" class="mt-0.5 rounded border-slate-700 text-indigo-600 focus:ring-indigo-500 bg-slate-800" {{ $settings['filter_zero_unit_months'] ? 'checked' : '' }}>
                        <div>
                            <span class="text-xs font-bold text-white block">Exclude 0-Unit / Frozen Meter Months</span>
                            <span class="text-[11px] text-slate-400 mt-0.5 block leading-normal">
                                Trims months with zero consumption (e.g. locked premises, frozen meters) from pulling down historical averages.
                            </span>
                        </div>
                    </label>
                </div>

                <!-- Global Spike Multiplier & Min Unit Buffer -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                    <!-- Global Spike Multiplier -->
                    <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                                Global Spike Multiplier
                            </label>
                            <p class="text-[11px] text-slate-500">
                                Months exceeding <code>Multiplier × Median</code> and buffer delta will be trimmed from average.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                                <button type="button" @click="adjustGlobalMultiplier(-0.1)" class="px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-bold transition">−</button>
                                <input type="number" step="0.1" min="1.0" max="10.0" name="global_spike_multiplier" x-model="globalSpikeMultiplier" class="w-20 bg-transparent border-0 text-center text-xs font-bold text-white focus:ring-0">
                                <button type="button" @click="adjustGlobalMultiplier(0.1)" class="px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-bold transition">+</button>
                            </div>
                            <span class="text-xs font-bold text-slate-400">× Median</span>
                        </div>

                        <!-- Presets -->
                        <div class="flex items-center gap-2 pt-1">
                            <span class="text-[10px] uppercase font-bold text-slate-500">Presets:</span>
                            @foreach([1.5, 2.0, 2.5, 3.0] as $preset)
                                <button type="button" @click="setGlobalMultiplier({{ $preset }})" :class="parseFloat(globalSpikeMultiplier) === {{ $preset }} ? 'bg-indigo-600 text-white font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'" class="px-2.5 py-1 text-[11px] rounded-lg border border-slate-700 transition">
                                    {{ number_format($preset, 1) }}x
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Minimum Unit Buffer (Delta Floor) -->
                    <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                                Minimum Spike Unit Buffer Floor
                            </label>
                            <p class="text-[11px] text-slate-500">
                                Absolute kWh delta floor required to flag a spike. Prevents small-number false positives (e.g. 5 to 12 kWh).
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                                <button type="button" @click="adjustBuffer(-5)" class="px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-bold transition">−</button>
                                <input type="number" step="1" min="0" max="500" name="min_spike_unit_buffer" x-model="minSpikeBuffer" class="w-20 bg-transparent border-0 text-center text-xs font-bold text-white focus:ring-0">
                                <button type="button" @click="adjustBuffer(5)" class="px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-bold transition">+</button>
                            </div>
                            <span class="text-xs font-bold text-slate-400">kWh Buffer Floor</span>
                        </div>

                        <div class="text-[11px] text-slate-400 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800/80">
                            💡 Delta must satisfy: <code>(Units − Median) &ge; Buffer</code>. If delta is below buffer, reading is safely retained.
                        </div>
                    </div>
                </div>

                <!-- Tariff Category Specific Overrides -->
                <div class="pt-4 border-t border-slate-800/80 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>🏷️</span>
                        <span>Tariff Category Overrides & Protections</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Agriculture IAS1 -->
                        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">IAS1 (Agriculture)</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="agriculture_spike_bypass" value="1" class="sr-only peer" {{ $settings['agriculture_spike_bypass'] ? 'checked' : '' }}>
                                    <div class="w-8 h-4 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>
                            <span class="text-xs font-bold text-white block">Seasonal Pump Surge Bypass</span>
                            <p class="text-[10px] text-slate-400 leading-relaxed">
                                When ON, agricultural irrigation surges are bypassed from trimming so seasonal pumping is preserved.
                            </p>
                            <div class="pt-2 border-t border-slate-800/60">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Fallback Multiplier (if bypass OFF)</label>
                                <div class="flex items-center bg-slate-950 border border-slate-800 rounded-lg overflow-hidden w-28">
                                    <button type="button" @click="adjustAgriMultiplier(-0.5)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">−</button>
                                    <input type="number" step="0.5" min="1.0" max="20.0" name="agriculture_spike_multiplier" x-model="agriMultiplier" class="w-12 bg-transparent border-0 text-center text-xs font-bold text-white focus:ring-0">
                                    <button type="button" @click="adjustAgriMultiplier(0.5)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">+</button>
                                </div>
                            </div>
                        </div>

                        <!-- Commercial NDS1D -->
                        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3">
                            <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 inline-block">NDS1D (Commercial)</span>
                            <span class="text-xs font-bold text-white block">Commercial Spike Multiplier</span>
                            <p class="text-[10px] text-slate-400 leading-relaxed">
                                Accommodates commercial equipment, refrigeration, and seasonal customer traffic swings.
                            </p>
                            <div class="pt-2 border-t border-slate-800/60">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Category Multiplier</label>
                                <div class="flex items-center bg-slate-950 border border-slate-800 rounded-lg overflow-hidden w-28">
                                    <button type="button" @click="adjustCommMultiplier(-0.1)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">−</button>
                                    <input type="number" step="0.1" min="1.0" max="10.0" name="commercial_spike_multiplier" x-model="commMultiplier" class="w-12 bg-transparent border-0 text-center text-xs font-bold text-white focus:ring-0">
                                    <button type="button" @click="adjustCommMultiplier(0.1)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">+</button>
                                </div>
                            </div>
                        </div>

                        <!-- Domestic DS1D, DS-II -->
                        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3">
                            <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 inline-block">DS1D / DS-II / KJ (Domestic)</span>
                            <span class="text-xs font-bold text-white block">Domestic Spike Multiplier</span>
                            <p class="text-[10px] text-slate-400 leading-relaxed">
                                Standard domestic threshold protecting residential households from sudden meter glitches or uncalibrated jumps.
                            </p>
                            <div class="pt-2 border-t border-slate-800/60">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Category Multiplier</label>
                                <div class="flex items-center bg-slate-950 border border-slate-800 rounded-lg overflow-hidden w-28">
                                    <button type="button" @click="adjustDomMultiplier(-0.1)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">−</button>
                                    <input type="number" step="0.1" min="1.0" max="10.0" name="domestic_spike_multiplier" x-model="domMultiplier" class="w-12 bg-transparent border-0 text-center text-xs font-bold text-white focus:ring-0">
                                    <button type="button" @click="adjustDomMultiplier(0.1)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Dynamic Visual Colorization & Threshold Ranges -->
            <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
                <input type="hidden" name="has_color_settings" value="1">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-800/80 pb-4 gap-3">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>🎨</span>
                            <span>Dynamic Visual Colorization & Threshold Ranges</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Real-time heatmap color gradients, usage zone spectrums, and visual alert thresholds across the Dashboard.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="resetColorDefaults()" class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-700/60 transition" title="Reset color and usage thresholds to factory defaults in form">
                            🔄 Reset Form Presets
                        </button>
                        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg transition" :class="colorizationEnabled ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700'" x-text="colorizationEnabled ? '🎨 Colorization: ON' : 'Colorization: OFF'">
                        </span>
                    </div>
                </div>

                <!-- Master Toggle & Quick Presets -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="space-y-1">
                            <span class="font-bold text-xs uppercase tracking-wider text-indigo-400 flex items-center gap-1.5">
                                <span>🌈</span>
                                <span>Master Colorization Engine</span>
                            </span>
                            <h3 class="text-sm font-bold text-white">Enable Dynamic Heatmap & Zone Spectrum</h3>
                            <p class="text-[11px] text-slate-400 leading-relaxed max-w-xl">
                                When enabled, bill amounts and average consumption units dynamically colorize across the card grid and table list views into Calm Green (Safe), Lime/Amber (Normal), Warm Orange (Elevated), and Bold Red (High-Alert).
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" name="dynamic_colorization_enabled" value="1" x-model="colorizationEnabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <!-- Quick Presets Bar -->
                    <div class="pt-3 border-t border-slate-800/60 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1">Quick Presets:</span>
                        <button type="button" @click="applyPreset('rural')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-950 hover:bg-slate-800 text-slate-300 border border-slate-700/60 transition active:scale-95 flex items-center gap-1.5">
                            <span>🌾</span>
                            <span>Rural Low-Cap</span>
                            <span class="text-[10px] text-slate-500 font-mono">(₹300 / ₹1k / ₹2k • 35/80/150 kWh)</span>
                        </button>
                        <button type="button" @click="applyPreset('balanced')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-950/40 hover:bg-indigo-900/50 text-indigo-300 border border-indigo-700/40 transition active:scale-95 flex items-center gap-1.5">
                            <span>⚖️</span>
                            <span>Balanced Default</span>
                            <span class="text-[10px] text-indigo-400/80 font-mono">(₹500 / ₹1.5k / ₹2.5k • 50/120/200 kWh)</span>
                        </button>
                        <button type="button" @click="applyPreset('urban')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-950 hover:bg-slate-800 text-slate-300 border border-slate-700/60 transition active:scale-95 flex items-center gap-1.5">
                            <span>🏙️</span>
                            <span>Urban High-Cap</span>
                            <span class="text-[10px] text-slate-500 font-mono">(₹800 / ₹2.5k / ₹5k • 80/200/350 kWh)</span>
                        </button>
                    </div>
                </div>

                <!-- Two-Column Grid: Bill Amount Thresholds & Average Unit Thresholds -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Column 1: Bill Amount Thresholds (₹) -->
                    <div class="p-5 sm:p-6 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-5">
                        <div class="border-b border-slate-800/80 pb-3">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>💰</span>
                                <span>Bill Amount Thresholds (₹)</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Defines numerical boundaries for green safe zones, amber warning ranges, and bold red high-alert triggers.
                            </p>
                        </div>

                        <!-- Amount Safe Ceiling -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                                    <span>Safe Zone Ceiling</span>
                                </label>
                                <span class="text-[11px] font-mono font-bold text-emerald-400" x-text="'&le; ₹' + (Number(amountSafeCeiling) || 0).toLocaleString()"></span>
                            </div>
                            <input type="number" step="10" min="0" name="color_amount_safe_ceiling" x-model="amountSafeCeiling" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-emerald-500 focus:border-emerald-500">
                            <span class="text-[10px] text-slate-500 block">Default: ₹500. Negative/Credit and amounts up to this ceiling display in Vibrant Green (Safe).</span>
                        </div>

                        <!-- Amount Warning Ceiling -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                                    <span>Warning Zone Ceiling</span>
                                </label>
                                <span class="text-[11px] font-mono font-bold text-amber-400" x-text="'&le; ₹' + (Number(amountWarningCeiling) || 0).toLocaleString()"></span>
                            </div>
                            <input type="number" step="10" min="0" name="color_amount_warning_ceiling" x-model="amountWarningCeiling" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-amber-500 focus:border-amber-500">
                            <span class="text-[10px] text-slate-500 block">Default: ₹1,500. Amounts between Safe and Warning ceilings display in Lime / Amber (Normal).</span>
                        </div>

                        <!-- Amount Danger Floor -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span>
                                    <span>High-Alert Danger Floor</span>
                                </label>
                                <span class="text-[11px] font-mono font-bold text-rose-400" x-text="'&ge; ₹' + (Number(amountDangerFloor) || 0).toLocaleString()"></span>
                            </div>
                            <input type="number" step="10" min="0" name="color_amount_danger_floor" x-model="amountDangerFloor" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-rose-500 focus:border-rose-500">
                            <span class="text-[10px] text-slate-500 block">Default: ₹2,500. Warning to Danger floor displays in Warm Orange. Floor+ displays in Bold Red with an Alert badge.</span>
                        </div>
                    </div>

                    <!-- Column 2: Average Units Thresholds (kWh) -->
                    <div class="p-5 sm:p-6 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-5">
                        <div class="border-b border-slate-800/80 pb-3">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>⚡</span>
                                <span>Average Usage Thresholds (kWh)</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Configures dynamic colorization and card border spectrum for Box 3 Average Usage and Table View units.
                            </p>
                        </div>

                        <!-- Units Safe Ceiling -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                                    <span>Low-Usage Safe Ceiling</span>
                                </label>
                                <span class="text-[11px] font-mono font-bold text-emerald-400" x-text="'&le; ' + (parseInt(unitsSafeCeiling) || 0) + ' kWh'"></span>
                            </div>
                            <input type="number" step="1" min="0" name="color_units_safe_ceiling" x-model="unitsSafeCeiling" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-emerald-500 focus:border-emerald-500">
                            <span class="text-[10px] text-slate-500 block">Default: 50 kWh. Readings and borders display in Deep Green (Safe Zone).</span>
                        </div>

                        <!-- Units Warning Ceiling -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                                    <span>Moderate Zone Ceiling</span>
                                </label>
                                <span class="text-[11px] font-mono font-bold text-amber-400" x-text="'&le; ' + (parseInt(unitsWarningCeiling) || 0) + ' kWh'"></span>
                            </div>
                            <input type="number" step="1" min="0" name="color_units_warning_ceiling" x-model="unitsWarningCeiling" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-amber-500 focus:border-amber-500">
                            <span class="text-[10px] text-slate-500 block">Default: 120 kWh. Moderate monthly consumption displays in Lime / Amber.</span>
                        </div>

                        <!-- Units Danger Floor -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span>
                                    <span>High-Usage Danger Floor</span>
                                </label>
                                <span class="text-[11px] font-mono font-bold text-rose-400" x-text="'&ge; ' + (parseInt(unitsDangerFloor) || 0) + ' kWh'"></span>
                            </div>
                            <input type="number" step="1" min="0" name="color_units_danger_floor" x-model="unitsDangerFloor" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-rose-500 focus:border-rose-500">
                            <span class="text-[10px] text-slate-500 block">Default: 200 kWh. High heavy-usage readings and borders display in Bold Red (Alert Zone).</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 5: Connection Endpoints & Credentials -->
            <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
                <div class="border-b border-slate-800/80 pb-4">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🔐</span>
                        <span>Connection Endpoints & Encryption Key</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Network endpoints and cryptographic passphrase required for OpenSSL EVP_BytesToKey AES-256-CBC payloads.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            WSS FluentGrid API Endpoint
                        </label>
                        <input type="url" name="wss_url" value="{{ old('wss_url', $settings['wss_url']) }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            WSS AES Passphrase / Key
                        </label>
                        <div class="relative" x-data="{ showKey: false }">
                            <input :type="showKey ? 'text' : 'password'" name="aes_key" value="{{ old('aes_key', $settings['aes_key']) }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                            <button type="button" @click="showKey = !showKey" class="absolute right-3 top-2.5 text-slate-500 hover:text-slate-300 text-xs">
                                <span x-text="showKey ? '🙈' : '👁️'"></span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Legacy BSPHCL ASMX Endpoint
                        </label>
                        <input type="url" name="legacy_url" value="{{ old('legacy_url', $settings['legacy_url']) }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Section 4: Performance & Concurrency Limits -->
            <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
                <div class="border-b border-slate-800/80 pb-4">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🚀</span>
                        <span>Performance & Multi-cURL Limits</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Tune simultaneous network connections and execution timeout thresholds.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Simultaneous Connections (Multi-cURL Concurrency)
                        </label>
                        <input type="number" name="concurrency" min="1" max="50" value="{{ old('concurrency', $settings['concurrency']) }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-indigo-500 focus:border-indigo-500">
                        <span class="text-[10px] text-slate-500 mt-1 block">Default: 10 connections. Higher values increase throughput but require more network bandwidth.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Request Timeout (Seconds)
                        </label>
                        <input type="number" name="timeout" min="5" max="180" value="{{ old('timeout', $settings['timeout']) }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-indigo-500 focus:border-indigo-500">
                        <span class="text-[10px] text-slate-500 mt-1 block">Default: 45s. Maximum time permitted for each single handle before timing out.</span>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-900 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                        <span>💾</span>
                        <span>Save Engine Configuration</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Section 5: Real-Time Diagnostic Sandbox -->
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
    </div>

    <script>
        function engineSettingsManager() {
            return {
                globalSpikeMultiplier: {{ old('global_spike_multiplier', $settings['global_spike_multiplier'] ?? 2.0) }},
                minSpikeBuffer: {{ old('min_spike_unit_buffer', $settings['min_spike_unit_buffer'] ?? 30) }},
                agriMultiplier: {{ old('agriculture_spike_multiplier', $settings['agriculture_spike_multiplier'] ?? 4.0) }},
                commMultiplier: {{ old('commercial_spike_multiplier', $settings['commercial_spike_multiplier'] ?? 2.5) }},
                domMultiplier: {{ old('domestic_spike_multiplier', $settings['domestic_spike_multiplier'] ?? 2.0) }},

                colorizationEnabled: {{ old('dynamic_colorization_enabled', $settings['dynamic_colorization_enabled'] ?? true) ? 'true' : 'false' }},
                amountSafeCeiling: {{ old('color_amount_safe_ceiling', $settings['color_amount_safe_ceiling'] ?? 500) }},
                amountWarningCeiling: {{ old('color_amount_warning_ceiling', $settings['color_amount_warning_ceiling'] ?? 1500) }},
                amountDangerFloor: {{ old('color_amount_danger_floor', $settings['color_amount_danger_floor'] ?? 2500) }},
                unitsSafeCeiling: {{ old('color_units_safe_ceiling', $settings['color_units_safe_ceiling'] ?? 50) }},
                unitsWarningCeiling: {{ old('color_units_warning_ceiling', $settings['color_units_warning_ceiling'] ?? 120) }},
                unitsDangerFloor: {{ old('color_units_danger_floor', $settings['color_units_danger_floor'] ?? 200) }},

                applyPreset(preset) {
                    if (preset === 'rural') {
                        this.amountSafeCeiling = 300;
                        this.amountWarningCeiling = 1000;
                        this.amountDangerFloor = 2000;
                        this.unitsSafeCeiling = 35;
                        this.unitsWarningCeiling = 80;
                        this.unitsDangerFloor = 150;
                    } else if (preset === 'balanced') {
                        this.amountSafeCeiling = 500;
                        this.amountWarningCeiling = 1500;
                        this.amountDangerFloor = 2500;
                        this.unitsSafeCeiling = 50;
                        this.unitsWarningCeiling = 120;
                        this.unitsDangerFloor = 200;
                    } else if (preset === 'urban') {
                        this.amountSafeCeiling = 800;
                        this.amountWarningCeiling = 2500;
                        this.amountDangerFloor = 5000;
                        this.unitsSafeCeiling = 80;
                        this.unitsWarningCeiling = 200;
                        this.unitsDangerFloor = 350;
                    }
                },
                resetColorDefaults() {
                    this.colorizationEnabled = true;
                    this.amountSafeCeiling = 500;
                    this.amountWarningCeiling = 1500;
                    this.amountDangerFloor = 2500;
                    this.unitsSafeCeiling = 50;
                    this.unitsWarningCeiling = 120;
                    this.unitsDangerFloor = 200;
                },

                setGlobalMultiplier(val) {
                    this.globalSpikeMultiplier = parseFloat(val).toFixed(1);
                },
                adjustGlobalMultiplier(delta) {
                    let current = parseFloat(this.globalSpikeMultiplier) || 2.0;
                    this.globalSpikeMultiplier = Math.max(1.0, Math.min(10.0, current + delta)).toFixed(1);
                },
                adjustBuffer(delta) {
                    let current = parseInt(this.minSpikeBuffer) || 30;
                    this.minSpikeBuffer = Math.max(0, Math.min(500, current + delta));
                },
                adjustAgriMultiplier(delta) {
                    let current = parseFloat(this.agriMultiplier) || 4.0;
                    this.agriMultiplier = Math.max(1.0, Math.min(20.0, current + delta)).toFixed(1);
                },
                adjustCommMultiplier(delta) {
                    let current = parseFloat(this.commMultiplier) || 2.5;
                    this.commMultiplier = Math.max(1.0, Math.min(10.0, current + delta)).toFixed(1);
                },
                adjustDomMultiplier(delta) {
                    let current = parseFloat(this.domMultiplier) || 2.0;
                    this.domMultiplier = Math.max(1.0, Math.min(10.0, current + delta)).toFixed(1);
                },
                resetSpikeDefaults() {
                    this.globalSpikeMultiplier = '2.0';
                    this.minSpikeBuffer = 30;
                    this.agriMultiplier = '4.0';
                    this.commMultiplier = '2.5';
                    this.domMultiplier = '2.0';
                },

                diagCa: '10230041576',
                diagDriver: 'auto',
                diagMonth: '{{ (int)date("n") }}',
                diagYear: '{{ (int)date("Y") }}',
                diagLoading: false,
                diagResult: null,

                async runDiagnostic() {
                    if (!this.diagCa.trim()) {
                        alert('Please enter a CA number for diagnostic testing.');
                        return;
                    }

                    this.diagLoading = true;
                    this.diagResult = null;

                    try {
                        const res = await fetch('{{ route("admin.bills.engine-settings.diagnostic") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ca_number: this.diagCa.trim(),
                                driver: this.diagDriver,
                                month: parseInt(this.diagMonth),
                                year: parseInt(this.diagYear)
                            })
                        });

                        const data = await res.json();
                        this.diagResult = data;
                    } catch (e) {
                        this.diagResult = {
                            success: false,
                            error: 'Network error while contacting diagnostic endpoint: ' + e.message
                        };
                    } finally {
                        this.diagLoading = false;
                    }
                }
            };
        }
    </script>
</x-admin-layout>
