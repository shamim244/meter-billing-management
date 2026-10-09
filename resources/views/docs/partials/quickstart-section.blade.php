<!-- Quickstart & Authentication Section -->
<section id="quickstart" class="space-y-6">
    <div class="border-b border-slate-800 pb-4">
        <h2 class="text-2xl font-black text-white tracking-tight">API Quickstart & Authentication</h2>
        <p class="text-xs text-slate-400 mt-1">Authenticate all requests with your secret Bearer token or direct X-API-Key header.</p>
    </div>

    <div class="glass-panel p-6 rounded-3xl space-y-4">
        <h3 class="text-sm font-bold text-white">Base Endpoint URL:</h3>
        <div class="p-3 rounded-xl bg-black/60 border border-slate-800 flex items-center justify-between font-mono text-xs text-cyan-300">
            <span>{{ $baseUrl }}</span>
            <button @click="copyCode('{{ $baseUrl }}')" class="text-slate-400 hover:text-white text-xs font-sans font-bold">Copy</button>
        </div>

        <h3 class="text-sm font-bold text-white mt-4">Authentication Headers (Choose either):</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] font-mono uppercase font-bold text-cyan-400 block mb-1">Method 1: Bearer Token (Standard)</span>
                <code class="font-mono text-xs text-slate-200">Authorization: Bearer <span class="text-cyan-300">{{ $activeKey ?? 'nbp_live_YOUR_KEY' }}</span></code>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] font-mono uppercase font-bold text-brand-400 block mb-1">Method 2: Direct API Key Header</span>
                <code class="font-mono text-xs text-slate-200">X-API-Key: <span class="text-cyan-300">{{ $activeKey ?? 'nbp_live_YOUR_KEY' }}</span></code>
            </div>
        </div>

        <!-- Intelligent Rate Limiting Overview -->
        <div class="mt-6 pt-4 border-t border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>🛡️</span>
                    <span>Intelligent Rate Limiting (Per Device / Key)</span>
                </h3>
                <span class="text-[11px] font-mono text-emerald-400 font-bold">Isolated per API Key</span>
            </div>
            <p class="text-xs text-slate-400">
                Quotas are isolated per API Key so devices sharing a Wi-Fi or cellular hotspot never block each other.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Reads & Queue</span>
                    <span class="text-base font-black text-cyan-300 font-mono mt-0.5 block">{{ $rateLimits['general_per_minute'] ?? 240 }} req / min</span>
                    <span class="text-[10px] text-slate-500">~{{ round(($rateLimits['general_per_minute'] ?? 240) / 60, 1) }} req/sec smooth browsing</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Review Verdicts</span>
                    <span class="text-base font-black text-amber-300 font-mono mt-0.5 block">{{ $rateLimits['review_per_minute'] ?? 120 }} rev / min</span>
                    <span class="text-[10px] text-slate-500">~{{ round(($rateLimits['review_per_minute'] ?? 120) / 60, 1) }} updates/sec field headroom</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Offline Batch Sync</span>
                    <span class="text-base font-black text-emerald-300 font-mono mt-0.5 block">{{ $rateLimits['batch_per_minute'] ?? 30 }} batches / min</span>
                    <span class="text-[10px] text-slate-500">Multi-record payload sync</span>
                </div>
            </div>
        </div>
    </div>
</section>
