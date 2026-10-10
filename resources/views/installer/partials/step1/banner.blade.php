@php
    $isReady = $preflight['server_ready'] ?? $preflight['ready'];
@endphp

<!-- Preflight Status Banner -->
@if($isReady)
    <div class="p-4 rounded-2xl bg-emerald-950/40 border border-emerald-800/60 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-base">✓</span>
            <div>
                <h3 class="text-xs font-bold text-emerald-300">Server Environment Ready</h3>
                <p class="text-[11px] text-emerald-400/80">PHP runtime, mandatory extensions, and storage directories are fully compatible.</p>
            </div>
        </div>
        <span class="px-2.5 py-1 text-[10px] font-black rounded-lg bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 uppercase tracking-wide">
            PASSED
        </span>
    </div>
@else
    <div class="p-4 rounded-2xl bg-rose-950/40 border border-rose-800/60 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="w-8 h-8 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center font-bold text-base">✗</span>
            <div>
                <h3 class="text-xs font-bold text-rose-300">Attention Required</h3>
                <p class="text-[11px] text-rose-400/80">Some mandatory extensions, PHP version (>= 8.4.1), or directory write permissions require configuration.</p>
            </div>
        </div>
        <span class="px-2.5 py-1 text-[10px] font-black rounded-lg bg-rose-500/10 text-rose-300 border border-rose-500/20 uppercase tracking-wide">
            ACTION NEEDED
        </span>
    </div>
@endif
