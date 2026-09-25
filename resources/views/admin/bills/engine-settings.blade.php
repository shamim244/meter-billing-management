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

            <!-- Section 3: Connection Endpoints & Credentials -->
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
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
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
                                <span class="text-slate-500 block text-[9px] uppercase">Meter Number</span>
                                <span class="text-white block" x-text="diagResult.extraction.meter_no || '—'"></span>
                            </div>
                            <div class="p-2 bg-slate-950 rounded-lg border border-slate-800">
                                <span class="text-slate-500 block text-[9px] uppercase">Tariff Category</span>
                                <span class="text-amber-300 block" x-text="diagResult.extraction.tariff_category || '—'"></span>
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
