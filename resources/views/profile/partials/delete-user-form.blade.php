<section class="space-y-4">
    <div class="flex items-center gap-3 mb-2">
        <div class="w-10 h-10 rounded-2xl bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400 flex items-center justify-center text-lg font-black">
            ⚠️
        </div>
        <div>
            <h2 class="text-lg font-black text-rose-600 dark:text-rose-400 tracking-tight">
                Delete Account (Danger Zone)
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Permanently remove your operator credentials and account data from the portal.
            </p>
        </div>
    </div>

    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
        Once your account is deleted, all assigned resources, preferences, and personal records will be permanently deleted. Please ensure you have downloaded any requisite billing reports before proceeding.
    </p>

    <div class="pt-2">
        <button type="button"
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow-md shadow-rose-500/20 transition flex items-center gap-2">
            <span>🗑️ Delete Account</span>
        </button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 bg-white dark:bg-slate-900 rounded-3xl">
            @csrf
            @method('delete')

            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-2xl bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400 flex items-center justify-center text-lg font-black">
                    🚨
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">
                        Confirm Account Deletion
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        This action cannot be undone.
                    </p>
                </div>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-400 mb-4 leading-relaxed">
                Please enter your current password below to permanently confirm you would like to delete your account.
            </p>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Your Password
                </label>
                <input id="password" 
                       name="password" 
                       type="password" 
                       class="w-full px-3.5 py-2.5 rounded-xl text-sm font-medium border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition" 
                       placeholder="Enter your password to confirm" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1.5" />
            </div>

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" 
                        x-on:click="$dispatch('close')" 
                        class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition text-center">
                    Cancel
                </button>
                <button type="submit" 
                        class="w-full sm:w-auto px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition shadow-md shadow-rose-500/20 text-center">
                    Yes, Delete My Account
                </button>
            </div>
        </form>
    </x-modal>
</section>
