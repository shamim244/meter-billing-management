{{-- Approve Modal --}}
<div x-show="approveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div @click.away="closeApprove()" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-bold">
                ✓
            </div>
            <div>
                <h3 class="text-base font-bold text-white">Approve Payment #<span x-text="currentPayment?.id"></span></h3>
                <p class="text-xs text-slate-400">Credit ₹<span x-text="currentPayment?.amount"></span> to Billing Agent</p>
            </div>
        </div>

        <form :action="'/admin/payments/' + currentPayment?.id + '/approve'" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Verification Notes (Optional)</label>
                <input type="text" name="notes" x-model="notes" placeholder="e.g. Verified against SBI Statement Ref 192837" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-emerald-500 focus:border-emerald-500">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="closeApprove()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-md shadow-emerald-600/30">
                    <span>✓</span> Confirm Approval
                </button>
            </div>
        </form>
    </div>
</div>
