<!-- ========================================== -->
<!-- TAB 3: RATE LIMITING & THROTTLING TIERS   -->
<!-- ========================================== -->
<div x-show="activeTab === 'ratelimits'" class="space-y-6" x-cloak>
    <!-- Master Throttle Switch -->
    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-lg" :class="rateLimiting ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-400 border border-rose-500/30'">
                <span x-text="rateLimiting ? '🛡️' : '⚠️'"></span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-white">Rate Limiting Enforcement</h3>
                    <span x-show="rateLimiting" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Active</span>
                    <span x-show="!rateLimiting" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30">Bypassed</span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">
                    When active, excessive requests receive <code class="text-rose-400 bg-slate-900 px-1 py-0.5 rounded">HTTP 429 Too Many Requests</code>. Turn off to grant unlimited throughput.
                </p>
            </div>
        </div>
        <div>
            <button type="button" @click="rateLimiting = !rateLimiting" class="relative inline-flex items-center cursor-pointer">
                <div class="w-14 h-7 bg-slate-800 rounded-full transition-colors duration-200" :class="rateLimiting ? 'bg-emerald-600' : 'bg-slate-800'">
                    <div class="w-6 h-6 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="rateLimiting ? 'translate-x-7' : 'translate-x-1'"></div>
                </div>
            </button>
        </div>
    </div>

    <!-- Tiers Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- General Reads -->
        <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-8 h-8 rounded-xl bg-cyan-500/15 text-cyan-400 flex items-center justify-center font-bold text-sm border border-cyan-500/30">🌐</span>
                    <span class="text-[10px] font-mono text-slate-500">api.general</span>
                </div>
                <h4 class="text-sm font-bold text-white">General Reads & Lookups</h4>
                <p class="text-xs text-slate-400 mt-1">Consumer lookups, MRU cycles, and automation queue reads.</p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-800/80">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold text-slate-300">Requests / Minute</label>
                    <span class="text-[10px] font-mono text-cyan-400" x-text="'~' + (general / 60).toFixed(1) + ' req/sec'"></span>
                </div>
                <input type="number" x-model="general" min="1" max="60000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
            </div>
        </div>

        <!-- Review Submissions -->
        <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-400 flex items-center justify-center font-bold text-sm border border-amber-500/30">📝</span>
                    <span class="text-[10px] font-mono text-slate-500">api.review</span>
                </div>
                <h4 class="text-sm font-bold text-white">Review Submissions</h4>
                <p class="text-xs text-slate-400 mt-1">Status changes, ledger updates, and doubt/critical flags.</p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-800/80">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold text-slate-300">Reviews / Minute</label>
                    <span class="text-[10px] font-mono text-amber-400" x-text="'~' + (review / 60).toFixed(1) + ' rev/sec'"></span>
                </div>
                <input type="number" x-model="review" min="1" max="30000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
            </div>
        </div>

        <!-- Batch Sync -->
        <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-8 h-8 rounded-xl bg-purple-500/15 text-purple-400 flex items-center justify-center font-bold text-sm border border-purple-500/30">📦</span>
                    <span class="text-[10px] font-mono text-slate-500">api.batch</span>
                </div>
                <h4 class="text-sm font-bold text-white">Batch Uploads</h4>
                <p class="text-xs text-slate-400 mt-1">Multi-record offline uploads carrying 50–100 bills each.</p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-800/80">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold text-slate-300">Batches / Minute</label>
                    <span class="text-[10px] font-mono text-purple-400" x-text="batch + ' batches/min'"></span>
                </div>
                <input type="number" x-model="batch" min="1" max="5000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
            </div>
        </div>

        <!-- Login Attempts -->
        <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-8 h-8 rounded-xl bg-rose-500/15 text-rose-400 flex items-center justify-center font-bold text-sm border border-rose-500/30">🔐</span>
                    <span class="text-[10px] font-mono text-slate-500">api.login</span>
                </div>
                <h4 class="text-sm font-bold text-white">Login Brute-Force Shield</h4>
                <p class="text-xs text-slate-400 mt-1">Prevents credential dictionary attacks per client IP.</p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-800/80">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold text-slate-300">Attempts / Minute</label>
                    <span class="text-[10px] font-mono text-rose-400" x-text="login + ' attempts/min'"></span>
                </div>
                <input type="number" x-model="login" min="1" max="1000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
            </div>
        </div>

        <!-- OpenAPI Spec -->
        <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="w-8 h-8 rounded-xl bg-teal-500/15 text-teal-400 flex items-center justify-center font-bold text-sm border border-teal-500/30">🤖</span>
                    <span class="text-[10px] font-mono text-slate-500">api.openapi</span>
                </div>
                <h4 class="text-sm font-bold text-white">AI Agent OpenAPI Spec</h4>
                <p class="text-xs text-slate-400 mt-1">Governs machine spec lookups from ChatGPT, Claude, and IDE tools.</p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-800/80">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold text-slate-300">Requests / Minute</label>
                    <span class="text-[10px] font-mono text-teal-400" x-text="openapi + ' req/min'"></span>
                </div>
                <input type="number" x-model="openapi" min="1" max="10000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
            </div>
        </div>

        <!-- Factory Defaults Card -->
        <div class="bg-gradient-to-br from-slate-950 to-indigo-950/40 p-6 rounded-3xl border border-indigo-500/20 shadow-xl flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 text-indigo-400 font-bold text-xs uppercase tracking-wider mb-2">
                    <span>🔄</span> Quick Preset
                </div>
                <h4 class="text-sm font-bold text-white">Factory Presets</h4>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Standard values (240 / 120 / 30 / 15 / 60) provide 4 req/sec smooth capacity while protecting MySQL.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-indigo-500/20">
                <button type="button" @click="confirmReset = true" class="w-full py-2 rounded-xl text-xs font-bold text-rose-400 hover:text-rose-300 bg-rose-950/30 hover:bg-rose-950/60 border border-rose-500/30 transition">
                    Reset All to Factory Defaults
                </button>
            </div>
        </div>
    </div>

    <!-- Save Action Bar -->
    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex items-center justify-between">
        <div class="text-xs text-slate-400">
            Changes apply across all clients in real-time.
        </div>
        <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-cyan-400 hover:from-emerald-300 hover:to-cyan-300 shadow-lg shadow-emerald-500/20 transition">
            Save Rate Limit Settings
        </button>
    </div>
</div>
