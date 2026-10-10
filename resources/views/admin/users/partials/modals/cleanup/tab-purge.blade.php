<!-- TAB 4: Full Account Purge -->
<div x-show="cleanupTab === 'purge'" class="space-y-4" x-cloak>
    <div class="p-3.5 rounded-2xl bg-rose-950/60 border border-rose-600/40 text-xs text-rose-200 space-y-2 leading-relaxed">
        <p class="font-bold text-white flex items-center gap-1.5">
            <span>⚠️</span> Irreversible Account & Storage Wipe
        </p>
        <p>This action permanently deletes everything for this agent:</p>
        <ul class="list-disc list-inside space-y-0.5 text-rose-300 font-mono text-[11px]">
            <li>User profile, login credentials & roles</li>
            <li>All stored PDF files on server disks</li>
            <li>MRUs, consumer accounts, & billing history</li>
            <li>Wallet ledger & active subscriptions</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('admin.users.purge', $user) }}" class="space-y-4">
        @csrf
        @method('DELETE')
        
        <div>
            <label class="block text-xs font-bold text-white mb-1">Type <code class="bg-black/40 px-1.5 py-0.5 rounded text-rose-300">DELETE</code> to confirm permanent account purge:</label>
            <input type="text" name="confirm_text" x-model="purgeConfirm" placeholder="DELETE" required class="w-full text-xs bg-slate-950 border-rose-500/50 rounded-xl text-white py-2.5 px-3 font-mono">
        </div>

        <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
            <button type="button" @click="showCleanupModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" :disabled="purgeConfirm !== 'DELETE'" class="px-5 py-2 text-xs font-black rounded-xl bg-rose-600 hover:bg-rose-500 text-white disabled:opacity-40 disabled:cursor-not-allowed shadow transition">
                Permanently Purge Entire Account
            </button>
        </div>
    </form>
</div>
