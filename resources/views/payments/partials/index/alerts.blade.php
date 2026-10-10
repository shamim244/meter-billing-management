<!-- Flash Alerts -->
@if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-sm">
        <span>✅ {{ session('success') }}</span>
        <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400">✕</button>
    </div>
@endif

@if(session('info'))
    <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 text-blue-800 dark:text-cyan-300 text-xs font-semibold flex items-center justify-between shadow-sm">
        <span>ℹ️ {{ session('info') }}</span>
        <button @click="$el.parentElement.remove()" class="text-blue-600 dark:text-cyan-400">✕</button>
    </div>
@endif
