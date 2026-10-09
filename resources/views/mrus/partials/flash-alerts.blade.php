<!-- Flash Alerts -->
@if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
            <span>✅</span> {{ session('success') }}
        </div>
        <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:opacity-75">✕</button>
    </div>
@endif

@if(session('info'))
    <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 text-blue-800 dark:text-cyan-300 text-xs font-semibold flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
            <span>ℹ️</span> {{ session('info') }}
        </div>
        <button @click="$el.parentElement.remove()" class="text-blue-600 dark:text-cyan-400 hover:opacity-75">✕</button>
    </div>
@endif

@if(session('warning'))
    <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs font-semibold flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
            <span>⚠️</span> {{ session('warning') }}
        </div>
        <button @click="$el.parentElement.remove()" class="text-amber-600 dark:text-amber-400 hover:opacity-75">✕</button>
    </div>
@endif

@if(session('error'))
    <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
            <span>❌</span> {{ session('error') }}
        </div>
        <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75">✕</button>
    </div>
@endif
