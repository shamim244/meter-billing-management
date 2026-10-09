<div x-show="showOverrideModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span>⚡</span> Force Subscription State Override
            </h3>
            <button type="button" @click="showOverrideModal = false" class="text-slate-400 hover:text-white text-lg font-bold">✕</button>
        </div>

        <p class="text-xs text-slate-400">
            Target Agent: <strong class="text-white" x-text="agentName"></strong> (Current: <span class="font-bold text-amber-400 uppercase" x-text="currentStatus"></span>)
        </p>

        <form method="POST" :action="'/admin/subscriptions/' + subId + '/state-override'" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Target Lifecycle State <span class="text-rose-400">*</span></label>
                <select name="target_status" x-model="targetStatus" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    <option value="active">ACTIVE (Full access restored)</option>
                    <option value="renewal_due">RENEWAL_DUE (Renewal warning banner)</option>
                    <option value="grace_period">GRACE_PERIOD (Countdown warning banner)</option>
                    <option value="suspended">SUSPENDED (Read-only mode)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Mandatory Override Reason <span class="text-rose-400">*</span></label>
                <textarea name="reason" required rows="3" placeholder="Explain the support reason or business authorization for this state change..." class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" @click="showOverrideModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-semibold">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                    Confirm Override
                </button>
            </div>
        </form>
    </div>
</div>
