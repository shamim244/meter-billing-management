{{-- Refund Modal --}}
<div x-show="refundModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div @click.away="closeRefund()" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-slate-800 text-slate-200 flex items-center justify-center text-xl font-bold">
                🔄
            </div>
            <div>
                <h3 class="text-base font-bold text-white">Log Manual Refund</h3>
                <p class="text-xs text-slate-400">Record refund action in audit log</p>
            </div>
        </div>

        <form :action="'/admin/payments/' + currentPayment?.id + '/refund'" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Refund Reason & Reference <span class="text-rose-400">*</span></label>
                <textarea name="refund_reason" x-model="refundReason" rows="3" required placeholder="e.g. Refunded ₹500 via UPI back to agent UPI ID on 21-Aug (Ref: 9912837)" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="closeRefund()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                    Cancel
                </button>
                <button type="submit" :disabled="!refundReason || refundReason.trim().length < 3" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-xs font-bold transition">
                    Log Refund
                </button>
            </div>
        </form>
    </div>
</div>
