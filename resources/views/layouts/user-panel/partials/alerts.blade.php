<!-- Flash Messages -->
<div class="px-4 sm:px-8 pt-4 sm:pt-6">
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm font-medium flex items-center gap-2 shadow-xs">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-500/30 text-rose-800 dark:text-rose-300 text-xs sm:text-sm font-medium flex items-center gap-2 shadow-xs">
            <span>❌</span> {{ session('error') }}
        </div>
    @endif
</div>
