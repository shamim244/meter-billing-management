<!-- Flash Messages -->
<div class="px-4 sm:px-8 pt-4 sm:pt-6">
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-500/30 text-emerald-300 text-xs sm:text-sm font-medium flex items-center gap-2">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-500/30 text-rose-300 text-xs sm:text-sm font-medium flex items-center gap-2">
            <span>❌</span> {{ session('error') }}
        </div>
    @endif
</div>
