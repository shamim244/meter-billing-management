<!-- LIVE "TRY IT OUT" API CONSOLE -->
<section id="try-it-out" class="space-y-6 pt-4">
    <div class="border-b border-slate-800 pb-4 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>🎮</span>
                <span>Interactive "Try It Out" API Console</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">Send live requests directly from your browser to test endpoints, dual-layer smart averages, and tuning guardrails.</p>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 animate-pulse">
            Live Tester Ready
        </span>
    </div>

    <div class="glass-panel rounded-3xl p-6 sm:p-8 space-y-6">
        <!-- Endpoint Selector & Key -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-bold text-slate-300 block mb-1">Target Endpoint</label>
                <select x-model="consoleEndpoint" @change="syncEndpointMethod(consoleEndpoint)" class="w-full text-xs rounded-xl border-slate-800 bg-slate-900 text-white p-2.5 font-mono">
                    <option value="GET /bills?status=pending">GET /bills?status=pending (Baseline Avg)</option>
                    <option value="GET /bills?status=pending&apply_tuning=true">GET /bills?status=pending&apply_tuning=true (Dual-Layer)</option>
                    <option value="GET /automation/queue">GET /automation/queue (Queue Baseline)</option>
                    <option value="GET /automation/queue?apply_tuning=true">GET /automation/queue?apply_tuning=true (Queue Tuned)</option>
                    <option value="POST /consumers/10230040377/average-tuning">POST /consumers/{ca}/average-tuning (Configure Tuning)</option>
                    <option value="GET /mrus">GET /mrus (All MRUs)</option>
                    <option value="GET /auth/me">GET /auth/me (Profile & Stats)</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="text-xs font-bold text-slate-300 block mb-1">API Key / Bearer Token</label>
                <input type="text" x-model="consoleApiKey" placeholder="Paste your nbp_live_... key here" class="w-full text-xs rounded-xl border-slate-800 bg-slate-900 text-cyan-300 font-mono p-2.5">
            </div>
        </div>

        <!-- Request Body Editor for POST / PATCH -->
        <div x-show="consoleMethod === 'POST' || consoleMethod === 'PATCH'" x-cloak class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-purple-300 flex items-center gap-1.5">
                    <span>⚙️</span>
                    <span>JSON Request Body (<span x-text="consoleMethod"></span>)</span>
                </label>
                <span class="text-[10px] text-slate-400 font-mono">Edit parameters to test guardrail clamping</span>
            </div>
            <textarea x-model="consoleBody" rows="5" class="w-full text-xs rounded-xl border-slate-800 bg-slate-900 text-purple-200 font-mono p-3 focus:ring-purple-500 focus:border-purple-500"></textarea>
        </div>

        <div class="flex items-center justify-between pt-2">
            <div class="text-[11px] text-slate-400 font-mono flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="consoleMethod === 'POST' ? 'bg-purple-500/20 text-purple-400 border border-purple-500/30' : (consoleMethod === 'PATCH' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-sky-500/20 text-sky-400 border border-sky-500/30')" x-text="consoleMethod"></span>
                <span>URL: <span class="text-white" x-text="'{{ $baseUrl }}' + getCleanPath()"></span></span>
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
                <span x-show="consoleStatusCode" x-cloak :class="consoleStatusCode === 200 || consoleStatusCode === 201 ? 'text-emerald-400' : 'text-rose-400'" class="font-bold">
                    Status: <span x-text="consoleStatusCode"></span> (<span x-text="consoleLatency + 'ms'"></span>)
                </span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 font-mono text-xs overflow-x-auto max-h-96 text-slate-200">
                <pre x-text="consoleResponse || 'Click \'Send Live Request\' above to test this endpoint...'"></pre>
            </div>
        </div>
    </div>
</section>
