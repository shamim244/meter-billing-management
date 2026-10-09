<!-- Option A: Instant PG Gateway (Razorpay / Cashfree) -->
<div x-show="mode === 'pg'" class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
        <span>3️⃣</span> Instant Checkout ({{ $settings['active_pg_driver'] === 'razorpay' ? 'Razorpay' : 'Cashfree' }})
    </h2>
    <div class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-xs space-y-2 text-indigo-900 dark:text-indigo-200">
        <div class="font-bold flex items-center gap-2">
            <span>⚡</span> Automatic Instant Verification & Credit
        </div>
        <p class="text-[11px] text-slate-600 dark:text-slate-400">
            Clicking "Proceed to Payment" will launch the secure {{ $settings['active_pg_driver'] === 'razorpay' ? 'Razorpay' : 'Cashfree' }} checkout modal. Supported: UPI apps (GPay, PhonePe, Paytm, CRED), Rupay & Visa/MasterCard debit/credit cards, and NetBanking.
        </p>
    </div>
</div>
