@if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-800/60 text-emerald-300 text-xs font-semibold flex items-center gap-3">
        <span class="text-base">✅</span>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if(session('warning'))
    <div class="p-4 rounded-2xl bg-amber-950/60 border border-amber-800/60 text-amber-300 text-xs font-semibold flex items-center gap-3">
        <span class="text-base">⚠️</span>
        <span>{{ session('warning') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-800/60 text-rose-300 text-xs font-semibold flex items-center gap-3">
        <span class="text-base">❌</span>
        <span>{{ session('error') }}</span>
    </div>
@endif
