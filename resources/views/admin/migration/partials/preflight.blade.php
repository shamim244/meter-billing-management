<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-black text-white flex items-center gap-2">
            <span>🔍</span> Current Server Health & Migration Readiness
        </h2>
        @if($preflight['ready'])
            <span class="px-3 py-1 text-[10px] font-black uppercase rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> 100% Ready
            </span>
        @else
            <span class="px-3 py-1 text-[10px] font-black uppercase rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                Attention Needed
            </span>
        @endif
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
            <span class="text-slate-400 block text-[11px]">PHP Version</span>
            <span class="text-white font-bold text-sm">{{ $preflight['php_version'] }}</span>
            <span class="text-[10px] block mt-0.5 {{ $preflight['php_satisfies'] ? 'text-emerald-400' : 'text-rose-400' }}">
                {{ $preflight['php_satisfies'] ? '✓ Satisfies >= 8.3' : '✗ Upgrade Required' }}
            </span>
        </div>

        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
            <span class="text-slate-400 block text-[11px]">Database Connection</span>
            <span class="text-white font-bold text-sm uppercase">{{ $preflight['database']['driver'] }}</span>
            <span class="text-[10px] block mt-0.5 {{ $preflight['database']['connected'] ? 'text-emerald-400' : 'text-rose-400' }}">
                {{ $preflight['database']['connected'] ? '✓ Connected & Active' : '✗ Failed' }}
            </span>
        </div>

        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
            <span class="text-slate-400 block text-[11px]">Redis Cache / Queue</span>
            <span class="text-white font-bold text-sm">{{ $preflight['redis']['connected'] ? 'Online' : 'Offline' }}</span>
            <span class="text-[10px] block mt-0.5 {{ $preflight['redis']['connected'] ? 'text-emerald-400' : 'text-slate-500' }}">
                {{ $preflight['redis']['connected'] ? '✓ In-Memory Accelerated' : '• Optional (File fallback)' }}
            </span>
        </div>

        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
            <span class="text-slate-400 block text-[11px]">Memory & Upload Limit</span>
            <span class="text-white font-bold text-sm">{{ $preflight['memory_limit'] }}</span>
            <span class="text-[10px] text-slate-400 block mt-0.5">Upload: {{ $preflight['upload_max_filesize'] }}</span>
        </div>
    </div>

    <!-- Extensions badges -->
    <div class="mt-4 pt-4 border-t border-slate-800 flex flex-wrap gap-2 text-[11px]">
        <span class="text-slate-400 font-semibold self-center mr-2">Extensions:</span>
        @foreach($preflight['extensions'] as $ext => $loaded)
            <span class="px-2 py-0.5 rounded-md font-mono text-[10px] font-bold border {{ $loaded ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20' : 'bg-rose-500/10 text-rose-300 border-rose-500/20' }}">
                {{ $ext }} {{ $loaded ? '✓' : '✗' }}
            </span>
        @endforeach
    </div>
</div>
