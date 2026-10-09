<!-- Navigation Bar -->
<header class="sticky top-0 z-40 w-full border-b border-white/5 bg-slate-950/80 backdrop-blur-xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        
        <!-- Logo -->
        <a href="/" class="flex items-center gap-3.5 group">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-brand-600 via-cyan-500 to-indigo-500 p-0.5 shadow-lg shadow-brand-500/25 group-hover:scale-105 transition-transform duration-300">
                <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center">
                    <span class="text-xl font-black text-cyan-400">⚡</span>
                </div>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-lg font-black tracking-tight text-white group-hover:text-cyan-300 transition-colors">NBPDCL Billing</span>
                    <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded-md bg-brand-500/10 text-cyan-400 border border-cyan-500/30">SAAS PRO</span>
                </div>
                <p class="text-[11px] text-slate-400 font-medium">Power Distribution Automation Suite</p>
            </div>
        </a>

        <!-- Desktop Menu -->
        <nav class="hidden lg:flex items-center gap-8 text-xs font-semibold text-slate-300">
            <a href="#comparison" class="hover:text-cyan-400 transition-colors">Why NBPDCL SaaS</a>
            <a href="#features" class="hover:text-cyan-400 transition-colors">Core Features</a>
            <a href="#interactive-demo" class="hover:text-cyan-400 transition-colors">4-Box Reading Demo</a>
            <a href="#roi-calculator" class="hover:text-cyan-400 transition-colors">ROI Calculator</a>
            <a href="#pricing" class="hover:text-cyan-400 transition-colors">Plans & Pricing</a>
            <a href="#faq" class="hover:text-cyan-400 transition-colors">FAQ</a>
        </nav>

        <!-- Auth & Action Buttons -->
        <div class="flex items-center gap-3">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-cyan-500 hover:from-brand-500 hover:to-cyan-400 text-white text-xs font-bold shadow-lg shadow-brand-500/20 transition-all duration-300 transform active:scale-95">
                        <span>📊 Go to Dashboard</span>
                        <span>→</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-slate-300 hover:text-white px-4 py-2 text-xs font-bold transition-colors">
                        Sign In
                    </a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 via-cyan-500 to-indigo-600 hover:from-brand-500 hover:to-cyan-400 text-white text-xs font-black shadow-lg shadow-brand-500/25 transition-all duration-300 transform hover:-translate-y-0.5 active:scale-95">
                            <span>Start Free 14-Day Trial</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    @endif
                @endauth
            @endif
        </div>

    </div>
</header>
