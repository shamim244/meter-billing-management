<!-- MODAL 1: GENERATE NEW KEY -->
<div x-show="createModalOpen" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <div @click.away="createModalOpen = false"
         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 relative">
        
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-brand-500/10 text-brand-600 dark:text-cyan-400 flex items-center justify-center text-lg font-bold">
                    🔑
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-900 dark:text-white">Generate Secret API Key</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Set key identity and expiration timeframe.</p>
                </div>
            </div>
            <button @click="createModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1">
                ✕
            </button>
        </div>

        <form method="POST" action="{{ route('user-panel.api-keys.store') }}" class="space-y-5">
            @csrf

            <!-- Name Field -->
            <div>
                <label for="name" class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">
                    Key Name / Device Description <span class="text-rose-500">*</span>
                </label>
                <input id="name" 
                       name="name" 
                       type="text" 
                       placeholder="e.g. Python ADB Tool, Field Phone A, Office Laptop" 
                       required 
                       class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-brand-500 p-3 font-medium">
                <p class="text-[11px] text-slate-400 mt-1">Identifies which tool or phone is using this key.</p>
            </div>

            <!-- Expiration Duration -->
            <div>
                <label for="duration" class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">
                    Expiration Timeframe <span class="text-rose-500">*</span>
                </label>
                <select id="duration" 
                        name="duration" 
                        class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-brand-500 p-3 font-semibold cursor-pointer">
                    <option value="1_day">⚡ 1 Day (Quick Field Test / Temporary)</option>
                    <option value="7_days">📅 7 Days (1 Week)</option>
                    <option value="30_days" selected>🗓️ 30 Days (1 Month — Recommended)</option>
                    <option value="90_days">📆 90 Days (Quarterly / 3 Months)</option>
                    <option value="365_days">⏳ 365 Days (1 Full Year)</option>
                    <option value="never">♾️ Never Expires</option>
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Automatic revocation after this date protects your account if a phone is lost.</p>
            </div>

            <!-- Abilities preset -->
            <div>
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Permission Scope</span>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-900 dark:text-white">Full Access (*)</div>
                        <div class="text-[10px] text-slate-400">Can read MRUs, query queue, submit reviews, and batch-sync readings.</div>
                    </div>
                    <span class="text-emerald-500 font-bold text-xs">✓ Included</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button @click="createModalOpen = false" 
                        type="button" 
                        class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-black shadow-lg shadow-brand-500/20 transition cursor-pointer">
                    Generate Secret Key →
                </button>
            </div>
        </form>
    </div>
</div>
