<!-- MODAL 2: CONFIRM REVOCATION -->
<div x-show="revokeModalOpen" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <div @click.away="revokeModalOpen = false"
         class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5">
        
        <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center text-xl font-bold">
            🗑️
        </div>

        <div>
            <h3 class="text-base font-black text-slate-900 dark:text-white">Revoke API Key?</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Are you sure you want to permanently revoke <strong class="text-slate-900 dark:text-white" x-text="revokeKeyName"></strong>?
            </p>
            <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-[11px] mt-3">
                ⚠️ Any Python script, phone tool, or background job currently using this key will immediately be denied access (401 Unauthorized).
            </div>
        </div>

        <form :action="'{{ url('/user-panel/api-keys') }}/' + revokeKeyId" method="POST" class="flex items-center justify-end gap-3 pt-2">
            @csrf
            @method('DELETE')

            <button @click="revokeModalOpen = false" 
                    type="button" 
                    class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold transition cursor-pointer">
                Cancel
            </button>
            <button type="submit" 
                    class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-black shadow-lg shadow-rose-600/20 transition cursor-pointer">
                Yes, Revoke Key
            </button>
        </form>
    </div>
</div>
