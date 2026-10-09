{{-- Active Cascading Chain Visualizer --}}
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4">
    <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
        <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>⛓️</span>
                <span>Cascading Fallback Hierarchy</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
                Client requests pass through this prioritized chain. If the client or server does not support an algorithm, it seamlessly degrades to the next available tier.
            </p>
        </div>
        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg {{ $status['enabled'] ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
            Status: {{ $status['enabled'] ? 'Active & Optimizing' : 'Disabled' }}
        </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 pt-2">
        @foreach($status['priority'] as $index => $algo)
            @php
                $isCapable = $status['capabilities'][$algo] ?? false;
                $isDisabled = in_array($algo, $status['disabled_by_admin'], true);
                $isActive = $isCapable && ! $isDisabled && $status['enabled'];
            @endphp
            <div class="p-4 rounded-2xl border transition relative {{ $isActive ? 'bg-slate-900/80 border-slate-700 ring-1 ring-slate-600' : 'bg-slate-950/40 border-slate-900 opacity-60' }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full {{ $isActive ? 'bg-cyan-500/20 text-cyan-400' : 'bg-slate-800 text-slate-500' }}">
                        Tier {{ $index + 1 }}
                    </span>
                    @if($isActive)
                        <span class="text-[10px] text-emerald-400 font-bold flex items-center gap-1">✅ Active</span>
                    @elseif($isDisabled)
                        <span class="text-[10px] text-amber-400 font-bold flex items-center gap-1">⚠️ Admin Disabled</span>
                    @else
                        <span class="text-[10px] text-slate-500 font-bold flex items-center gap-1">❌ Unavailable</span>
                    @endif
                </div>
                <h4 class="text-sm font-bold text-white capitalize">{{ $algo === 'br' ? 'Brotli' : $algo }}</h4>
                <p class="text-[11px] text-slate-400 mt-1">
                    @if($algo === 'zstd')
                        Preferred for JSON/CSV (Speed First)
                    @elseif($algo === 'br')
                        Preferred for HTML/Text (Max Ratio)
                    @elseif($algo === 'gzip')
                        Standard RFC 1952 Fallback
                    @else
                        Raw zlib Deflate Stream
                    @endif
                </p>
            </div>
        @endforeach
    </div>
</div>
