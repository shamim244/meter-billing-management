{{-- Reject Modal (Mandatory Reason Required) --}}
<div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div @click.away="closeReject()" class="bg-slate-900 border border-rose-500/30 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-xl font-bold">
                ❌
            </div>
            <div>
                <h3 class="text-base font-bold text-white">Reject Payment</h3>
                <p class="text-xs text-rose-400">A mandatory rejection reason is required</p>
            </div>
        </div>

        <form :action="'/admin/payments/' + currentPayment?.id + '/reject'" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Rejection Reason <span class="text-rose-400">*</span></label>
                <textarea name="rejection_reason" x-model="rejectionReason" rows="3" required placeholder="e.g. UTR number not found in bank statement, amount mismatch (claimed ₹1000, received ₹500)" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-rose-500 focus:border-rose-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Internal Notes (Optional)</label>
                <input type="text" name="notes" x-model="notes" placeholder="Contacted billing agent via phone on 21-Aug" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-rose-500 focus:border-rose-500">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="closeReject()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                    Cancel
                </button>
                <button type="submit" :disabled="!rejectionReason || rejectionReason.trim().length < 3" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-md shadow-rose-600/30">
                    <span>❌</span> Reject Payment
                </button>
            </div>
        </form>
    </div>
</div>
