{{-- Extension Status Grid --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    {{-- Zstandard Card --}}
    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 font-bold text-sm">⚡</span>
                <div>
                    <h3 class="text-sm font-black text-white">Zstandard (Zstd)</h3>
                    <p class="text-[11px] text-slate-400">High-throughput real-time engine</p>
                </div>
            </div>
            @if($status['capabilities']['zstd'])
                <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Loaded
                </span>
            @else
                <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    Missing
                </span>
            @endif
        </div>
        <div class="mt-4 pt-4 border-t border-slate-800/80 space-y-2 text-xs">
            <div class="flex justify-between text-slate-400">
                <span>Installed Version:</span>
                <span class="font-mono text-slate-200">{{ $status['extension_versions']['zstd'] }}</span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Optimal Target:</span>
                <span class="text-cyan-400 font-semibold">JSON APIs & CSV Logs</span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Speed Advantage:</span>
                <span class="text-slate-300">3x – 5x faster than Gzip</span>
            </div>
        </div>
    </div>

    {{-- Brotli Card --}}
    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold text-sm">🌐</span>
                <div>
                    <h3 class="text-sm font-black text-white">Brotli (br)</h3>
                    <p class="text-[11px] text-slate-400">Maximum compression density</p>
                </div>
            </div>
            @if($status['capabilities']['br'])
                <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Loaded
                </span>
            @else
                <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    Missing
                </span>
            @endif
        </div>
        <div class="mt-4 pt-4 border-t border-slate-800/80 space-y-2 text-xs">
            <div class="flex justify-between text-slate-400">
                <span>Installed Version:</span>
                <span class="font-mono text-slate-200">{{ $status['extension_versions']['brotli'] }}</span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Optimal Target:</span>
                <span class="text-indigo-400 font-semibold">HTML, CSS, XML</span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Ratio Advantage:</span>
                <span class="text-slate-300">~20% smaller than Gzip</span>
            </div>
        </div>
    </div>

    {{-- Gzip / Deflate Card --}}
    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold text-sm">📦</span>
                <div>
                    <h3 class="text-sm font-black text-white">Gzip / Deflate (zlib)</h3>
                    <p class="text-[11px] text-slate-400">Universal compatibility fallback</p>
                </div>
            </div>
            @if($status['capabilities']['gzip'])
                <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Loaded
                </span>
            @else
                <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                    Missing
                </span>
            @endif
        </div>
        <div class="mt-4 pt-4 border-t border-slate-800/80 space-y-2 text-xs">
            <div class="flex justify-between text-slate-400">
                <span>Installed Version:</span>
                <span class="font-mono text-slate-200">{{ $status['extension_versions']['zlib'] }}</span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Optimal Target:</span>
                <span class="text-emerald-400 font-semibold">Legacy Clients & Fallback</span>
            </div>
            <div class="flex justify-between text-slate-400">
                <span>Compatibility:</span>
                <span class="text-slate-300">100% universal across web</span>
            </div>
        </div>
    </div>
</div>
