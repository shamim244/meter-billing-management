{{-- Top Overview & Info Card --}}
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-500/15 text-indigo-400 border border-indigo-500/30">
                    Automation Engine
                </span>
                <span class="text-xs text-slate-500">•</span>
                <span class="text-xs text-slate-400 font-medium">NBPDCL REST API v1</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>⚡</span> API Rate Limits & Throttling Controls
            </h1>
            <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
                Control real-time request ceilings per API key and client token. Dynamic throttling protects MySQL and PHP-FPM from runaway script loops while ensuring field readers experience zero lag. Changes apply instantly across all nodes without restarting workers.
            </p>
        </div>

        {{-- Live Metrics Tiles --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Keys</div>
                <div class="text-xl font-black text-white font-mono mt-0.5">{{ $stats['total_api_keys'] }}</div>
            </div>
            <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Keys</div>
                <div class="text-xl font-black text-emerald-400 font-mono mt-0.5">{{ $stats['active_api_keys'] }}</div>
            </div>
            <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tokens</div>
                <div class="text-xl font-black text-cyan-400 font-mono mt-0.5">{{ $stats['total_tokens'] }}</div>
            </div>
            <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Preset</div>
                <div class="text-xs font-bold font-mono mt-1.5 {{ $stats['is_customized'] ? 'text-amber-400' : 'text-slate-400' }}">
                    {{ $stats['is_customized'] ? 'Custom' : 'Factory' }}
                </div>
            </div>
        </div>
    </div>
</div>
