<!-- LIVE "TRY IT OUT" API CONSOLE -->
<section id="try-it-out" class="space-y-6 pt-4">
    <div class="border-b border-slate-800 pb-4 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>🎮</span>
                <span>Interactive "Try It Out" API Console</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">Send live requests directly from your browser to test endpoints and response times.</p>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 animate-pulse">
            Live Tester Ready
        </span>
    </div>

    <div class="glass-panel rounded-3xl p-6 sm:p-8 space-y-6">
        <!-- Endpoint Selector -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-bold text-slate-300 block mb-1">Target Endpoint</label>
                <select x-model="consoleEndpoint" class="w-full text-xs rounded-xl border-slate-800 bg-slate-900 text-white p-2.5 font-mono">
                    <option value="/auth/me">GET /auth/me (Profile & Stats)</option>
                    <option value="/mrus">GET /mrus (All MRU Workspaces)</option>
                    <option value="/bills?status=pending">GET /bills?status=pending</option>
                    <option value="/automation/queue">GET /automation/queue</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="text-xs font-bold text-slate-300 block mb-1">API Key / Bearer Token</label>
                <input type="text" x-model="consoleApiKey" placeholder="Paste your nbp_live_... key here" class="w-full text-xs rounded-xl border-slate-800 bg-slate-900 text-cyan-300 font-mono p-2.5">
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <div class="text-[11px] text-slate-400 font-mono">
                URL: <span class="text-white" x-text="'{{ $baseUrl }}' + consoleEndpoint"></span>
            </div>
            <button @click="sendLiveRequest()" :disabled="consoleLoading" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-cyan-500 hover:from-brand-500 hover:to-cyan-400 text-white text-xs font-black shadow-lg shadow-brand-500/20 transition cursor-pointer flex items-center gap-2">
                <span x-show="!consoleLoading">🚀 Send Live Request</span>
                <span x-show="consoleLoading" x-cloak>⏳ Sending...</span>
            </button>
        </div>

        <!-- Response Output Window -->
        <div class="space-y-2 pt-2">
            <div class="flex items-center justify-between text-xs font-mono text-slate-400">
                <span>Response Output</span>
                <span x-show="consoleStatusCode" x-cloak :class="consoleStatusCode === 200 ? 'text-emerald-400' : 'text-rose-400'" class="font-bold">
                    Status: <span x-text="consoleStatusCode"></span> (<span x-text="consoleLatency + 'ms'"></span>)
                </span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 font-mono text-xs overflow-x-auto max-h-96 text-slate-200">
                <pre x-text="consoleResponse || 'Click \'Send Live Request\' above to test this endpoint...'"></pre>
            </div>
        </div>
    </div>
</section>
