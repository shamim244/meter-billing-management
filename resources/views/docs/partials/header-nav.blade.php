<!-- Top Navigation Bar -->
<header class="sticky top-0 z-50 w-full border-b border-slate-800 bg-slate-950/90 backdrop-blur-xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-cyan-400 p-0.5 shadow-md shadow-brand-500/20 group-hover:scale-105 transition">
                    <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center font-bold text-cyan-400 text-sm">
                        ⚡
                    </div>
                </div>
                <div>
                    <span class="font-extrabold text-sm tracking-tight text-white group-hover:text-brand-300 transition">NBPDCL Developers</span>
                    <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-widest block leading-none">REST API v1</span>
                </div>
            </a>
        </div>

        <div class="flex items-center gap-3 text-xs font-semibold">
            <a href="{{ $openapiUrl }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-cyan-400 border border-slate-800 transition font-mono text-[11px]">
                <span>📄</span>
                <span>openapi.json</span>
            </a>

            @auth
                <a href="{{ route('user-panel.api-keys') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                    <span>🔑</span>
                    <span>Manage Keys</span>
                </a>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold transition shadow-sm">
                    <span>Dashboard →</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="text-slate-400 hover:text-white transition px-3 py-1.5">
                    Sign In
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold transition shadow-sm">
                    Get API Key →
                </a>
            @endauth
        </div>
    </div>
</header>
