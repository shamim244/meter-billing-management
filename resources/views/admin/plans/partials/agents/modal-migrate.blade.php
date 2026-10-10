<!-- Plan Migration Modal -->
<div x-show="showMigrateModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span>🔄</span> Migrate Agent Subscription
            </h3>
            <button type="button" @click="showMigrateModal = false" class="text-slate-400 hover:text-white text-lg font-bold">✕</button>
        </div>

        <p class="text-xs text-slate-400">
            Migrating agent <strong class="text-white" x-text="selectedUserName"></strong> will deactivate their current plan and issue a new locked contract snapshot.
        </p>

        <form method="POST" action="{{ route('admin.plans.migrate_agent') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="user_id" :value="selectedUserId">

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Target Plan <span class="text-rose-400">*</span></label>
                <select name="target_plan_id" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    @foreach($allPlans as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->included_mrus }} MRUs / {{ $p->included_consumers }} CAs)</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Duration <span class="text-rose-400">*</span></label>
                <select name="duration_months" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    <option value="1">1 Month</option>
                    <option value="2">2 Months</option>
                    <option value="3">3 Months</option>
                    <option value="6">6 Months</option>
                    <option value="12">12 Months</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" @click="showMigrateModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-semibold">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                    Confirm Migration
                </button>
            </div>
        </form>
    </div>
</div>
