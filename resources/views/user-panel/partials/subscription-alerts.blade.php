{{-- Session Flash Alerts --}}
@if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-sm">
        <span>{{ session('success') }}</span>
        <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 font-bold">✕</button>
    </div>
@endif

@if(session('error'))
    <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
        <span>❌ {{ session('error') }}</span>
        <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 font-bold">✕</button>
    </div>
@endif

@if(session('info'))
    <div class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800/60 text-indigo-800 dark:text-indigo-300 text-xs font-semibold flex items-center justify-between shadow-sm">
        <span>ℹ️ {{ session('info') }}</span>
        <button @click="$el.parentElement.remove()" class="text-indigo-600 dark:text-indigo-400 font-bold">✕</button>
    </div>
@endif
