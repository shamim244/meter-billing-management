<!-- Top Overview & Info Card -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-500/15 text-indigo-400 border border-indigo-500/30">
                    Enterprise Control Hub
                </span>
                <span class="text-xs text-slate-500">•</span>
                <span class="text-xs text-slate-400 font-medium">NBPDCL REST API v1</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                <span>⚡</span> API & Automation Control Hub
            </h1>
            <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
                Comprehensive administrative cockpit for API traffic analytics, dynamic rate limiting, granular feature switches, security policies, and client key lifecycle management.
            </p>
        </div>

        <!-- Top Telemetry Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Today</div>
                <div class="text-xl font-black text-cyan-400 font-mono mt-0.5">{{ number_format($requestsToday) }}</div>
            </div>
            <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">This Month</div>
                <div class="text-xl font-black text-indigo-400 font-mono mt-0.5">{{ number_format($requestsThisMonth) }}</div>
            </div>
            <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Keys</div>
                <div class="text-xl font-black text-emerald-400 font-mono mt-0.5">{{ $stats['active_keys'] }}</div>
            </div>
            <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Avg Latency</div>
                <div class="text-xl font-black text-white font-mono mt-0.5">{{ $avgLatencyMs }}<span class="text-xs font-normal text-slate-400">ms</span></div>
            </div>
        </div>
    </div>

    <!-- Tab Switcher Navigation -->
    <div class="flex items-center gap-2 mt-8 pt-6 border-t border-slate-800/80 overflow-x-auto pb-1 text-xs">
        <button type="button" @click="activeTab = 'analytics'"
            class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap"
            :class="activeTab === 'analytics' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'">
            <span>📊</span>
            <span>Traffic Analytics</span>
        </button>

        <button type="button" @click="activeTab = 'features'"
            class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap"
            :class="activeTab === 'features' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'">
            <span>🎛️</span>
            <span>Feature Switches</span>
        </button>

        <button type="button" @click="activeTab = 'ratelimits'"
            class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap"
            :class="activeTab === 'ratelimits' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'">
            <span>⏱️</span>
            <span>Rate Limiting</span>
        </button>

        <button type="button" @click="activeTab = 'policies'"
            class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap"
            :class="activeTab === 'policies' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'">
            <span>🔑</span>
            <span>Security Policies</span>
        </button>

        <button type="button" @click="activeTab = 'keys'"
            class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap"
            :class="activeTab === 'keys' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'">
            <span>📋</span>
            <span>Issued Keys Ledger</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono">{{ $stats['total_keys'] }}</span>
        </button>
    </div>
</div>
