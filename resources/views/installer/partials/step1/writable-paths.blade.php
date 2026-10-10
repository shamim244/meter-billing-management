<!-- Directory Write Permissions -->
<div class="space-y-2">
    <h4 class="text-xs font-bold text-slate-300">Writable Directories</h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
        @foreach($preflight['writable_paths'] as $path => $writable)
            <div class="p-2.5 rounded-xl border flex items-center justify-between {{ $writable ? 'bg-slate-950/40 border-slate-800 text-slate-300' : 'bg-rose-950/20 border-rose-800/40 text-rose-300' }}">
                <span class="font-mono text-[11px] font-semibold">{{ $path }}</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $writable ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                    {{ $writable ? 'WRITABLE' : 'READ-ONLY' }}
                </span>
            </div>
        @endforeach
    </div>
</div>
