{{-- Tool 1: Instant Gateway Checkout Simulator --}}
<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-5">
    <div class="border-b border-slate-900 pb-3">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
            <span>⚡</span> 1. Mock Gateway Checkout Simulator
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">Simulate instant card/UPI checkout success or failure for any registered billing agent.</p>
    </div>

    <form action="{{ route('admin.payments.simulator.checkout') }}" method="POST" class="space-y-4">
        @csrf

        <!-- Select Agent -->
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Billing Agent / User <span class="text-rose-400">*</span></label>
            <select name="user_id" required class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>

        <!-- Amount & Purpose -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Amount (₹) <span class="text-rose-400">*</span></label>
                <input type="number" step="1" min="1" name="amount" value="1000" required class="w-full text-xs font-mono font-bold bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Payment Purpose</label>
                <select name="purpose" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    <option value="wallet_topup">👛 Wallet Top-Up</option>
                    <option value="direct_subscription">⭐ Direct Subscription</option>
                </select>
            </div>
        </div>

        <!-- Gateway Provider & Outcome -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Payment Gateway</label>
                <select name="gateway" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    <option value="razorpay">Razorpay Standard</option>
                    <option value="cashfree">Cashfree PG</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Simulation Outcome</label>
                <select name="outcome" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-bold">
                    <option value="success" class="text-emerald-400">🟢 SUCCESS (Auto-Approved)</option>
                    <option value="failed" class="text-rose-400">🔴 FAILED (Declined / Dropped)</option>
                </select>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-2">
            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2">
                <span>🚀</span> Run Simulation & Credit Ledger
            </button>
        </div>
    </form>
</div>
