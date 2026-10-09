<!-- 2. Payment Method Selection -->
@php
    $pgAvailable = $settings['pg_enabled'] && ($settings['cashfree_enabled'] || $settings['razorpay_enabled']);
    $hasAnyChannel = $pgAvailable || $settings['manual_upi_enabled'] || $settings['bank_transfer_enabled'];
@endphp

@if(!$hasAnyChannel)
    <div class="p-6 rounded-3xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-center space-y-2">
        <div class="text-3xl">⚠️</div>
        <div class="text-sm font-bold">Payment Channels Under Scheduled Maintenance</div>
        <p class="text-xs text-slate-400 max-w-md mx-auto">All payment gateways and manual transfer channels are temporarily paused by the administration. Please check back later or contact support.</p>
    </div>
@else
    <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
            <span>2️⃣</span> Select Payment Mode
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @if($pgAvailable)
                <label :class="mode === 'pg' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 shadow-sm' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer flex flex-col justify-between space-y-3 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">⚡</span>
                        <input type="radio" name="mode" value="pg" x-model="mode" class="text-indigo-600 focus:ring-indigo-500">
                    </div>
                    <div>
                        <div class="font-extrabold text-xs">
                            Online PG ({{ $settings['active_pg_driver'] === 'razorpay' ? 'Razorpay' : 'Cashfree' }})
                        </div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">UPI, Cards, NetBanking (Instant Activation)</div>
                    </div>
                </label>
            @endif

            @if($settings['manual_upi_enabled'])
                <label :class="mode === 'manual_upi' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 shadow-sm' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer flex flex-col justify-between space-y-3 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">📱</span>
                        <input type="radio" name="mode" value="manual_upi" x-model="mode" class="text-indigo-600 focus:ring-indigo-500">
                    </div>
                    <div>
                        <div class="font-extrabold text-xs">Manual UPI QR</div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Pay to UPI ID + Submit UTR (Zero Fee)</div>
                    </div>
                </label>
            @endif

            @if($settings['bank_transfer_enabled'])
                <label :class="mode === 'bank_transfer' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 shadow-sm' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer flex flex-col justify-between space-y-3 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">🏦</span>
                        <input type="radio" name="mode" value="bank_transfer" x-model="mode" class="text-indigo-600 focus:ring-indigo-500">
                    </div>
                    <div>
                        <div class="font-extrabold text-xs">Bank Transfer (NEFT)</div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Direct IMPS / NEFT to Business Account</div>
                    </div>
                </label>
            @endif
        </div>
    </div>
@endif
