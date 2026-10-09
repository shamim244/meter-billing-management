<!-- MODAL 2: Override Quotas -->
<div x-show="showQuotaModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="showQuotaModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>🎯</span> Custom Quota & Rate Overrides
            </h3>
            <button type="button" @click="showQuotaModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.users.override-quotas', $user) }}" class="space-y-4">
            @csrf
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Included MRUs Locked</label>
                    <input type="number" name="included_mrus_locked" value="{{ $user->activeSubscription->included_mrus_locked ?? ($user->activeSubscription->plan->included_mrus ?? 1) }}" min="1" max="1000" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Included Consumers Locked</label>
                    <input type="number" name="included_consumers_locked" value="{{ $user->activeSubscription->included_consumers_locked ?? ($user->activeSubscription->plan->included_consumers ?? 500) }}" min="10" max="1000000" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Extra MRU Rate (₹)</label>
                    <input type="number" step="0.01" name="extra_mru_rate_locked" value="{{ $user->activeSubscription->extra_mru_rate_locked ?? 20.00 }}" min="0" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Extra Consumer Rate (₹)</label>
                    <input type="number" step="0.01" name="extra_consumer_rate_locked" value="{{ $user->activeSubscription->extra_consumer_rate_locked ?? 0.20 }}" min="0" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 font-mono">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                <button type="button" @click="showQuotaModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-amber-600 hover:bg-amber-500 text-white shadow">Save Quota Overrides</button>
            </div>
        </form>
    </div>
</div>
