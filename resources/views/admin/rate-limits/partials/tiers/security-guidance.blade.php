{{-- 4. Login Brute-Force Tier --}}
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
    <div>
        <div class="flex items-center justify-between gap-2 mb-3">
            <span class="w-8 h-8 rounded-xl bg-rose-500/15 text-rose-400 flex items-center justify-center font-bold text-sm border border-rose-500/30">🔐</span>
            <span class="text-[10px] font-mono text-slate-500">api.login</span>
        </div>
        <h4 class="text-sm font-bold text-white">Mobile & Token Login</h4>
        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
            Throttles credential authentication to defend against brute-force and credential-stuffing dictionary attacks.
        </p>
        <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
            <div>POST /api/v1/auth/login</div>
            <div>Keyed by: Client IP</div>
        </div>
    </div>

    <div class="mt-6 pt-4 border-t border-slate-800/80">
        <div class="flex items-center justify-between mb-1.5">
            <label for="login_per_minute" class="text-xs font-semibold text-slate-300">Attempts / Minute</label>
            <span class="text-[10px] font-mono text-rose-400" x-text="login + ' attempts/min'"></span>
        </div>
        <div class="relative">
            <input type="number" id="login_per_minute" name="login_per_minute" x-model="login" min="1" max="1000" required
                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
        </div>
        <div class="flex items-center justify-between mt-2 text-[10px] text-slate-500">
            <span>Default: <strong class="text-slate-400 font-mono">{{ $defaults['login_per_minute'] }}</strong></span>
            <button type="button" @click="login = {{ $defaults['login_per_minute'] }}" class="text-indigo-400 hover:underline">Use default</button>
        </div>
    </div>
</div>

{{-- 5. OpenAPI Schema Tier --}}
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
    <div>
        <div class="flex items-center justify-between gap-2 mb-3">
            <span class="w-8 h-8 rounded-xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center font-bold text-sm border border-emerald-500/30">🤖</span>
            <span class="text-[10px] font-mono text-slate-500">api.openapi</span>
        </div>
        <h4 class="text-sm font-bold text-white">AI Agent OpenAPI Spec</h4>
        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
            Governs schema ingestion requests from AI agents (Cursor, ChatGPT, Claude) querying API tools specifications.
        </p>
        <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
            <div>GET /api/v1/openapi.json</div>
            <div>Keyed by: Client IP</div>
        </div>
    </div>

    <div class="mt-6 pt-4 border-t border-slate-800/80">
        <div class="flex items-center justify-between mb-1.5">
            <label for="openapi_per_minute" class="text-xs font-semibold text-slate-300">Requests / Minute</label>
            <span class="text-[10px] font-mono text-emerald-400" x-text="openapi + ' req/min'"></span>
        </div>
        <div class="relative">
            <input type="number" id="openapi_per_minute" name="openapi_per_minute" x-model="openapi" min="1" max="10000" required
                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
        </div>
        <div class="flex items-center justify-between mt-2 text-[10px] text-slate-500">
            <span>Default: <strong class="text-slate-400 font-mono">{{ $defaults['openapi_per_minute'] }}</strong></span>
            <button type="button" @click="openapi = {{ $defaults['openapi_per_minute'] }}" class="text-indigo-400 hover:underline">Use default</button>
        </div>
    </div>
</div>

{{-- 6. Summary Card & Guidance --}}
<div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950/40 p-6 rounded-3xl border border-indigo-500/20 shadow-xl flex flex-col justify-between">
    <div>
        <div class="flex items-center gap-2 text-indigo-400 font-bold text-xs uppercase tracking-wider mb-2">
            <span>💡</span> Recommendation
        </div>
        <h4 class="text-sm font-bold text-white">Balancing Speed & Safety</h4>
        <p class="text-xs text-slate-300 mt-2 leading-relaxed">
            Rate limit counters are stored in high-speed RAM cache with <strong class="text-white">&lt; 1ms</strong> check latency. 
        </p>
        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
            Field operators share quotas based on their individual <code class="text-cyan-300 bg-slate-900 px-1 py-0.5 rounded text-[11px]">API Key ID</code> rather than IP address, preventing office hotspots from blocking teammates.
        </p>
    </div>

    <div class="mt-6 pt-4 border-t border-indigo-500/20">
        <a href="{{ route('docs.api') }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">
            <span>📖 Live Developer Portal & Console</span>
            <span>↗</span>
        </a>
    </div>
</div>
