<!-- Header & Navigation -->
<div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
        <a href="{{ route('user-panel.subscription') }}" class="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition">
            ← Back to Plans
        </a>
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>⭐</span> Subscription Purchase Confirmation
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Review plan quotas, duration discount, and choose your payment method.</p>
        </div>
    </div>

    <div class="hidden sm:block">
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
            Fixed Pricing Guarantee
        </span>
    </div>
</div>
