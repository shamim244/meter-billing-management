<!-- Success & Error Session Alerts -->
@if (session('success'))
    <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 text-emerald-800 dark:text-emerald-300 text-xs flex items-center gap-3 shadow-sm">
        <span class="text-base">✅</span>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-300 text-xs flex items-center gap-3 shadow-sm">
        <span class="text-base">⚠️</span>
        <span>{{ session('error') }}</span>
    </div>
@endif
