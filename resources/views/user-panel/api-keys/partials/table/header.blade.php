<div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
    <div>
        <h2 class="text-base font-bold text-slate-900 dark:text-white">Active API Keys</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Keys permitted to query, update readings, and batch-sync data on your behalf.</p>
    </div>
    <span class="text-xs font-mono font-bold text-slate-400">
        {{ $apiKeys->count() }} {{ Str::plural('Key', $apiKeys->count()) }}
    </span>
</div>
