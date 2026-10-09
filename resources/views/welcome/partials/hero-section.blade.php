<!-- 1. HERO SECTION -->
<section class="relative pt-16 pb-20 md:pt-24 md:pb-32 overflow-hidden">
    <!-- Radiant Lighting Effects -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[400px] bg-brand-500/15 blur-[140px] rounded-full pointer-events-none"></div>
    <div class="absolute top-1/3 left-1/4 -translate-x-1/2 -translate-y-1/2 w-[450px] h-[300px] bg-cyan-500/15 blur-[110px] rounded-full pointer-events-none"></div>
    <div class="absolute top-1/3 right-1/4 translate-x-1/2 -translate-y-1/2 w-[450px] h-[300px] bg-indigo-500/15 blur-[110px] rounded-full pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        
        <!-- Trust / Authority Badge -->
        <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-slate-900/90 border border-cyan-500/30 text-cyan-300 text-xs font-bold shadow-inner mb-8 animate-pulse-slow">
            <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
            <span>Trusted by 500+ Meter Readers & Energy Agencies Across Bihar</span>
        </div>

        <!-- Main Hero Headline -->
        <h1 class="text-4xl sm:text-6xl md:text-7xl font-black tracking-tight text-white mb-8 max-w-5xl mx-auto leading-[1.12]">
            Automate 50,000+ Electricity Bills. <br class="hidden sm:inline" />
            <span class="text-gradient-cyan">Download, Decode & Audit in Minutes.</span>
        </h1>

        <!-- Hero Subtitle -->
        <p class="text-slate-400 text-base sm:text-lg md:text-xl max-w-3xl mx-auto mb-10 leading-relaxed font-medium">
            Stop wasting days downloading single bills manually. Our high-concurrency multi-stream engine, Kruti-Dev Hindi PDF OCR, and 4-Box Reading Ledger automate your entire monthly billing operations with zero calculation errors.
        </p>

        <!-- CTAs & Proof Points -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
            <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 rounded-2xl bg-gradient-to-r from-brand-600 via-cyan-500 to-indigo-600 hover:from-brand-500 hover:to-cyan-400 text-white text-sm font-black shadow-xl shadow-brand-500/25 transition-all duration-300 transform hover:-translate-y-0.5">
                <span>🚀 Start Free 14-Day Trial</span>
                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-md font-mono">No Credit Card</span>
            </a>
            <a href="#interactive-demo" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl glass-panel text-slate-200 hover:text-white hover:border-cyan-500/40 text-sm font-bold transition-all duration-300">
                <span>⚡ Try Live 4-Box Reading Demo</span>
                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </a>
        </div>

        <!-- Key Marketing Metric Counters -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 max-w-5xl mx-auto">
            <div class="glass-panel p-5 rounded-2xl text-left border border-white/5 shadow-sm">
                <div class="text-2xl sm:text-3xl font-black text-cyan-400 font-mono">100+ /sec</div>
                <div class="text-xs font-bold text-white mt-1">Multi-Stream Throughput</div>
                <div class="text-[11px] text-slate-400">Parallel cURL pipeline</div>
            </div>
            <div class="glass-panel p-5 rounded-2xl text-left border border-white/5 shadow-sm">
                <div class="text-2xl sm:text-3xl font-black text-brand-400 font-mono">99.98%</div>
                <div class="text-xs font-bold text-white mt-1">Kruti-Dev OCR Accuracy</div>
                <div class="text-[11px] text-slate-400">Zero broken Hindi glyphs</div>
            </div>
            <div class="glass-panel p-5 rounded-2xl text-left border border-white/5 shadow-sm">
                <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono">4-Box</div>
                <div class="text-xs font-bold text-white mt-1">Meter Ledger Invariant</div>
                <div class="text-[11px] text-slate-400">Guarantees Working ≥ PDF</div>
            </div>
            <div class="glass-panel p-5 rounded-2xl text-left border border-white/5 shadow-sm">
                <div class="text-2xl sm:text-3xl font-black text-amber-400 font-mono">1-Click</div>
                <div class="text-xs font-bold text-white mt-1">ZIP & CSV Batch Export</div>
                <div class="text-[11px] text-slate-400">Ready for DISCOM submittal</div>
            </div>
        </div>

    </div>
</section>
