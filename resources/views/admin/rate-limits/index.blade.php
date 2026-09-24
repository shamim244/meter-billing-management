<x-admin-layout>
    <x-slot name="header">
        API Rate Limiting & Automation Throttling
    </x-slot>

    <div x-data="{
        enabled: {{ $currentLimits['enabled'] ? 'true' : 'false' }},
        general: {{ (int) $currentLimits['general_per_minute'] }},
        review: {{ (int) $currentLimits['review_per_minute'] }},
        batch: {{ (int) $currentLimits['batch_per_minute'] }},
        login: {{ (int) $currentLimits['login_per_minute'] }},
        openapi: {{ (int) $currentLimits['openapi_per_minute'] }},
        confirmReset: false
    }" class="space-y-8">

        <!-- Status Notification Banner -->
        @if (session('status'))
            <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs shadow-lg flex items-center justify-between gap-3 animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="text-base">✅</span>
                    <span class="font-semibold">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs shadow-lg space-y-1">
                <div class="font-bold flex items-center gap-2 text-rose-200">
                    <span>⚠️</span> Validation Errors:
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-rose-300/90 pl-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Top Overview & Info Card -->
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

                <!-- Live Metrics Tiles -->
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

        <!-- Main Configuration Form -->
        <form action="{{ route('admin.rate_limits.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Master Toggle Card -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-lg" :class="enabled ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-400 border border-rose-500/30'">
                        <span x-text="enabled ? '🛡️' : '⚠️'"></span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-white">Global Rate Limiting Enforcement</h3>
                            <span x-show="enabled" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Active</span>
                            <span x-show="!enabled" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30">Bypassed</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            When enabled, requests exceeding configured quotas receive standard <code class="text-rose-400 bg-slate-900 px-1 py-0.5 rounded">HTTP 429 Too Many Requests</code>. When disabled, all endpoints allow unlimited traffic.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 self-end sm:self-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="enabled" value="1" x-model="enabled" class="sr-only peer">
                        <div class="w-14 h-7 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
            </div>

            <!-- Tiered Limits Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- 1. General Reads Tier -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="w-8 h-8 rounded-xl bg-cyan-500/15 text-cyan-400 flex items-center justify-center font-bold text-sm border border-cyan-500/30">🌐</span>
                            <span class="text-[10px] font-mono text-slate-500">api.general</span>
                        </div>
                        <h4 class="text-sm font-bold text-white">General Reads & Lookups</h4>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Applies to consumer searching, MRU cycle fetching, bill queries, and queue status reads.
                        </p>
                        <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
                            <div>GET /api/v1/bills</div>
                            <div>GET /api/v1/mrus</div>
                            <div>GET /api/v1/automation/queue</div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800/80">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="general_per_minute" class="text-xs font-semibold text-slate-300">Requests / Minute</label>
                            <span class="text-[10px] font-mono text-cyan-400" x-text="'~' + (general / 60).toFixed(1) + ' req/sec'"></span>
                        </div>
                        <div class="relative">
                            <input type="number" id="general_per_minute" name="general_per_minute" x-model="general" min="1" max="60000" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        </div>
                        <div class="flex items-center justify-between mt-2 text-[10px] text-slate-500">
                            <span>Default: <strong class="text-slate-400 font-mono">{{ $defaults['general_per_minute'] }}</strong></span>
                            <button type="button" @click="general = {{ $defaults['general_per_minute'] }}" class="text-indigo-400 hover:underline">Use default</button>
                        </div>
                    </div>
                </div>

                <!-- 2. Review Submissions Tier -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-400 flex items-center justify-center font-bold text-sm border border-amber-500/30">📝</span>
                            <span class="text-[10px] font-mono text-slate-500">api.review</span>
                        </div>
                        <h4 class="text-sm font-bold text-white">Review Ledger Submissions</h4>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Applies to ledger modifications, human reviews, doubt/critical flagging, and ADB status pushes.
                        </p>
                        <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
                            <div>PATCH /api/v1/bills/review</div>
                            <div>POST /api/v1/automation/update-status</div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800/80">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="review_per_minute" class="text-xs font-semibold text-slate-300">Reviews / Minute</label>
                            <span class="text-[10px] font-mono text-amber-400" x-text="'~' + (review / 60).toFixed(1) + ' reviews/sec'"></span>
                        </div>
                        <div class="relative">
                            <input type="number" id="review_per_minute" name="review_per_minute" x-model="review" min="1" max="30000" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        </div>
                        <div class="flex items-center justify-between mt-2 text-[10px] text-slate-500">
                            <span>Default: <strong class="text-slate-400 font-mono">{{ $defaults['review_per_minute'] }}</strong></span>
                            <button type="button" @click="review = {{ $defaults['review_per_minute'] }}" class="text-indigo-400 hover:underline">Use default</button>
                        </div>
                    </div>
                </div>

                <!-- 3. Batch Sync Tier -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="w-8 h-8 rounded-xl bg-purple-500/15 text-purple-400 flex items-center justify-center font-bold text-sm border border-purple-500/30">📦</span>
                            <span class="text-[10px] font-mono text-slate-500">api.batch</span>
                        </div>
                        <h4 class="text-sm font-bold text-white">Batch Sync & Bulk Uploads</h4>
                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                            Applies to heavy multi-record sync payloads. Each batch typically carries 50–100 offline reading records.
                        </p>
                        <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
                            <div>POST /api/v1/bills/batch-sync</div>
                            <div>POST /api/v1/sync/readings/batch</div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800/80">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="batch_per_minute" class="text-xs font-semibold text-slate-300">Batches / Minute</label>
                            <span class="text-[10px] font-mono text-purple-400" x-text="batch + ' batches/min'"></span>
                        </div>
                        <div class="relative">
                            <input type="number" id="batch_per_minute" name="batch_per_minute" x-model="batch" min="1" max="5000" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        </div>
                        <div class="flex items-center justify-between mt-2 text-[10px] text-slate-500">
                            <span>Default: <strong class="text-slate-400 font-mono">{{ $defaults['batch_per_minute'] }}</strong></span>
                            <button type="button" @click="batch = {{ $defaults['batch_per_minute'] }}" class="text-indigo-400 hover:underline">Use default</button>
                        </div>
                    </div>
                </div>

                <!-- 4. Login Brute-Force Tier -->
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

                <!-- 5. OpenAPI Schema Tier -->
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

                <!-- 6. Summary Card & Guidance -->
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
            </div>

            <!-- Save Action Bar -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-slate-400">
                    Changes take effect immediately across all active API clients.
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <button type="button" @click="confirmReset = true" class="px-5 py-2.5 rounded-xl text-xs font-bold text-rose-400 hover:text-rose-300 bg-rose-950/30 hover:bg-rose-950/60 border border-rose-500/30 transition">
                        Reset Defaults
                    </button>
                    <button type="submit" class="flex-1 sm:flex-initial px-6 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-cyan-400 hover:from-emerald-300 hover:to-cyan-300 shadow-lg shadow-emerald-500/20 transition">
                        Save Rate Limit Settings
                    </button>
                </div>
            </div>
        </form>

        <!-- Reset Confirmation Modal -->
        <div x-show="confirmReset" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="confirmReset = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/15 text-rose-400 flex items-center justify-center font-bold text-xl border border-rose-500/30 mx-auto">
                    🔄
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-bold text-white">Reset Rate Limits?</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        This will restore all rate limiting thresholds back to the factory defaults (General: 240/min, Review: 120/min, Batch: 30/min, Login: 15/min, OpenAPI: 60/min).
                    </p>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="confirmReset = false" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 rounded-xl transition">
                        Cancel
                    </button>
                    <form action="{{ route('admin.rate_limits.reset') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-xl transition shadow-lg shadow-rose-600/30">
                            Yes, Reset to Defaults
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
