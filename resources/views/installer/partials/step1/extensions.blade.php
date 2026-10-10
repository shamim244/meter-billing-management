<!-- Required PHP Extensions -->
<div class="space-y-2">
    <h4 class="text-xs font-bold text-slate-300">PHP Extensions</h4>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
        @foreach($preflight['extensions'] as $ext => $loaded)
            <div class="p-2.5 rounded-xl border flex items-center justify-between {{ $loaded ? 'bg-slate-950/40 border-slate-800 text-slate-300' : 'bg-rose-950/20 border-rose-800/40 text-rose-300' }}">
                <span class="font-mono text-[11px] font-semibold">{{ $ext }}</span>
                <span class="text-xs">{{ $loaded ? '✅' : '❌' }}</span>
            </div>
        @endforeach
    </div>
</div>
