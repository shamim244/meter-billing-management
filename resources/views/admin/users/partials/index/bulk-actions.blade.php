<div x-show="selectedUsers.length > 0" x-cloak class="bg-indigo-950/90 border border-indigo-500/40 p-4 rounded-2xl shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 animate-in fade-in slide-in-from-bottom-2 duration-150">
    <div class="flex items-center gap-2 text-xs font-bold text-indigo-200">
        <span class="px-2 py-0.5 bg-indigo-600 text-white rounded-md font-mono" x-text="selectedUsers.length"></span>
        <span>user(s) selected</span>
    </div>

    <form method="POST" action="{{ route('admin.users.bulk-action') }}" class="flex flex-wrap items-center gap-2">
        @csrf
        <template x-for="id in selectedUsers" :key="id">
            <input type="hidden" name="user_ids[]" :value="id">
        </template>

        <select name="bulk_action" x-model="bulkAction" required class="text-xs bg-slate-950 border border-indigo-400/40 rounded-xl px-3 py-1.5 text-white">
            <option value="">-- Choose Bulk Action --</option>
            <option value="activate">✓ Activate Accounts</option>
            <option value="suspend">🚫 Suspend Accounts</option>
            <option value="change_plan_tier">⚡ Change Plan Tier</option>
            <option value="delete">🗑️ Purge Accounts & Files</option>
        </select>

        <select x-show="bulkAction === 'change_plan_tier'" name="plan_tier" x-model="bulkPlanTier" class="text-xs bg-slate-950 border border-indigo-400/40 rounded-xl px-3 py-1.5 text-white">
            <option value="free">Free Tier</option>
            <option value="starter">Starter</option>
            <option value="pro">Pro Operator</option>
            <option value="enterprise">Enterprise Hub</option>
        </select>

        <button type="submit" @click="confirmBulkAction($event)" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow transition">
            Apply Action
        </button>
    </form>
</div>
