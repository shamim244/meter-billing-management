{{-- 1. General Reads Tier --}}
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

{{-- 2. Review Submissions Tier --}}
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

{{-- 3. Batch Sync Tier --}}
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
