@if($payment->status->value === 'pending_verification')
    <div class="bg-slate-950 p-6 rounded-2xl border border-amber-500/30 space-y-4">
        <h2 class="text-sm font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
            <span>⚡</span> Quick Actions
        </h2>

        <!-- Approve Form -->
        <form action="{{ route('admin.payments.approve', $payment->id) }}" method="POST" class="space-y-3">
            @csrf
            <input type="text" name="notes" placeholder="Approval notes (optional)" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-emerald-500">
            <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1 shadow-md shadow-emerald-600/30">
                <span>✅</span> Approve Payment
            </button>
        </form>

        <div class="border-t border-slate-800/80 pt-3">
            <!-- Reject Form -->
            <form action="{{ route('admin.payments.reject', $payment->id) }}" method="POST" class="space-y-3">
                @csrf
                <textarea name="rejection_reason" rows="2" required placeholder="Mandatory rejection reason..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-rose-500"></textarea>
                <button type="submit" class="w-full py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1 shadow-md shadow-rose-600/30">
                    <span>❌</span> Reject Payment
                </button>
            </form>
        </div>
    </div>
@endif
