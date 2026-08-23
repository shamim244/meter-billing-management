<x-app-layout>
    <div x-data="{
        testMode: 'pg_razorpay',
        purpose: 'wallet_topup',
        amount: 500,
        outcome: 'success',
        isSubmitting: false,
        setAmount(val) {
            this.amount = val;
        }
    }" class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Top Header & Breadcrumb -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('payments.index') }}" class="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition">
                        ← Back to Ledger
                    </a>
                    <div>
                        <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>🧪</span> Payment Sandbox & Test Checkout Playground
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Test online gateways and mock balance top-ups without using real money.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('payments.create') }}" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-xs font-bold text-slate-800 dark:text-slate-200 transition flex items-center gap-1.5">
                        <span>💳</span> Switch to Live Checkout
                    </a>
                </div>
            </div>

            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <span>✅ {{ session('success') }}</span>
                    <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 font-bold">✕</button>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <span>❌ {{ session('error') }}</span>
                    <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 font-bold">✕</button>
                </div>
            @endif

            <!-- Banner Notice -->
            <div class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-200 space-y-1">
                <div class="font-bold flex items-center gap-2">
                    <span>⚡</span> Instant Sandbox Test Environment
                </div>
                <p class="text-[11px] text-slate-600 dark:text-slate-400">
                    Use this page to simulate real billing agent transactions. When you trigger a successful payment, your account balance will immediately reflect the credit and update your transaction history.
                </p>
            </div>

            <!-- Main Interactive Testing Terminal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left 2 Cols: Form Terminal -->
                <div class="lg:col-span-2 space-y-6">
                    <form action="{{ route('payments.sandbox.checkout') }}" method="POST" @submit="isSubmitting = true" class="space-y-6">
                        @csrf

                        <!-- 1. Channel Selector -->
                        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                                <span>1️⃣</span> Select Test Payment Method
                            </h2>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Razorpay -->
                                <label :class="testMode === 'pg_razorpay' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer flex items-center gap-3 transition">
                                    <input type="radio" name="test_mode" value="pg_razorpay" x-model="testMode" class="text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <div class="font-bold text-xs">⚡ Razorpay Standard PG</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400">Simulate Razorpay instant card/UPI</div>
                                    </div>
                                </label>

                                <!-- Cashfree -->
                                <label :class="testMode === 'pg_cashfree' ? 'border-cyan-500 bg-cyan-50/50 dark:bg-cyan-950/40 text-cyan-900 dark:text-cyan-200' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer flex items-center gap-3 transition">
                                    <input type="radio" name="test_mode" value="pg_cashfree" x-model="testMode" class="text-cyan-600 focus:ring-cyan-500">
                                    <div>
                                        <div class="font-bold text-xs">💳 Cashfree Payments PG</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400">Simulate Cashfree dropin checkout</div>
                                    </div>
                                </label>

                                <!-- Manual UPI -->
                                <label :class="testMode === 'manual_upi' ? 'border-purple-500 bg-purple-50/50 dark:bg-purple-950/40 text-purple-900 dark:text-purple-200' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer flex items-center gap-3 transition">
                                    <input type="radio" name="test_mode" value="manual_upi" x-model="testMode" class="text-purple-600 focus:ring-purple-500">
                                    <div>
                                        <div class="font-bold text-xs">📱 Manual UPI QR (UTR Claim)</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400">Simulates UTR submission for admin queue</div>
                                    </div>
                                </label>

                                <!-- Bank Transfer -->
                                <label :class="testMode === 'bank_transfer' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/40 text-blue-900 dark:text-blue-200' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer flex items-center gap-3 transition">
                                    <input type="radio" name="test_mode" value="bank_transfer" x-model="testMode" class="text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <div class="font-bold text-xs">🏦 Bank Transfer (NEFT/IMPS)</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400">Simulates direct bank reference claim</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- 2. Purpose & Amount -->
                        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                                <span>2️⃣</span> Select Purpose & Mock Amount
                            </h2>

                            <!-- Purpose -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label :class="purpose === 'wallet_topup' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-3.5 rounded-2xl border-2 cursor-pointer flex items-center gap-3 transition">
                                    <input type="radio" name="purpose" value="wallet_topup" x-model="purpose" class="text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <div class="font-bold text-xs">👛 Wallet Top-Up</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400">Prepaid balance for bill downloading</div>
                                    </div>
                                </label>

                                <label :class="purpose === 'direct_subscription' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-3.5 rounded-2xl border-2 cursor-pointer flex items-center gap-3 transition">
                                    <input type="radio" name="purpose" value="direct_subscription" x-model="purpose" class="text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <div class="font-bold text-xs">⭐ Direct Subscription</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400">Tier upgrade / plan renewal</div>
                                    </div>
                                </label>
                            </div>

                            <!-- Amount -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Test Amount (₹)</label>
                                <div class="relative max-w-sm">
                                    <span class="absolute left-4 top-3 text-slate-400 font-bold text-base">₹</span>
                                    <input type="number" step="1" min="1" name="amount" x-model.number="amount" required class="w-full text-base font-black font-mono pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-indigo-500">
                                </div>

                                <div class="flex flex-wrap items-center gap-2 mt-3">
                                    <span class="text-[11px] text-slate-400">Quick Test Amounts:</span>
                                    <button type="button" @click="setAmount(100)" class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold transition">₹100</button>
                                    <button type="button" @click="setAmount(500)" class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold transition">₹500</button>
                                    <button type="button" @click="setAmount(1000)" class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold transition">₹1,000</button>
                                    <button type="button" @click="setAmount(2500)" class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold transition">₹2,500</button>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Simulation Outcome -->
                        <div x-show="testMode === 'pg_razorpay' || testMode === 'pg_cashfree'" class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                                <span>3️⃣</span> Choose Simulation Result
                            </h2>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label :class="outcome === 'success' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer flex items-center gap-3 transition">
                                    <input type="radio" name="outcome" value="success" x-model="outcome" class="text-emerald-600 focus:ring-emerald-500">
                                    <div>
                                        <div class="font-bold text-xs text-emerald-600 dark:text-emerald-400">🟢 Simulate Success</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400">Instant approval & balance credit</div>
                                    </div>
                                </label>

                                <label :class="outcome === 'failed' ? 'border-rose-500 bg-rose-50/50 dark:bg-rose-950/40 text-rose-900 dark:text-rose-200' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer flex items-center gap-3 transition">
                                    <input type="radio" name="outcome" value="failed" x-model="outcome" class="text-rose-600 focus:ring-rose-500">
                                    <div>
                                        <div class="font-bold text-xs text-rose-600 dark:text-rose-400">🔴 Simulate Decline / Failure</div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400">Simulate bank refusal / insufficient balance</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Action -->
                        <div>
                            <button type="submit" :disabled="isSubmitting || amount < 1" class="w-full py-3.5 bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 disabled:opacity-50 text-white rounded-2xl text-xs font-bold shadow-lg shadow-indigo-500/25 transition flex items-center justify-center gap-2">
                                <span x-show="!isSubmitting">⚡</span>
                                <span x-show="isSubmitting" class="animate-spin">⏳</span>
                                <span x-text="isSubmitting ? 'Processing Test Transaction...' : 'Run Test Payment (Instant Sandbox)'"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right 1 Col: Test Cheat Sheet & User Stats -->
                <div class="space-y-6">

                    <!-- My Account Summary -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">My Billing Account</span>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-lg font-bold">
                                👤
                            </div>
                            <div>
                                <div class="font-bold text-xs text-slate-900 dark:text-white">{{ $user->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $user->email }}</div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2.5 bg-slate-50 dark:bg-slate-950 rounded-xl">
                                <span class="text-[10px] text-slate-400 block">Total Paid</span>
                                <span class="font-mono font-bold text-emerald-500">₹{{ number_format($stats['total_paid'], 2) }}</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 dark:bg-slate-950 rounded-xl">
                                <span class="text-[10px] text-slate-400 block">Pending Queue</span>
                                <span class="font-mono font-bold text-amber-500">{{ $stats['pending_count'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Test Card Cheat Sheet -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3 text-xs">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                            <span>💳</span> Sandbox Test Credentials
                        </span>

                        <div class="space-y-2 text-[11px]">
                            <div class="p-2.5 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 space-y-1">
                                <span class="text-slate-400 block text-[10px]">Test Card (Visa / Rupay)</span>
                                <div class="font-mono font-bold text-indigo-400 select-all">4111 1111 1111 1111</div>
                                <div class="text-slate-500 text-[10px]">Expiry: 12/28 • CVV: 123 • OTP: 123456</div>
                            </div>

                            <div class="p-2.5 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 space-y-1">
                                <span class="text-slate-400 block text-[10px]">Test UPI VPAs</span>
                                <div class="font-mono font-bold text-cyan-400 select-all">success@upi</div>
                                <div class="font-mono text-rose-400 select-all text-[10px]">failure@upi</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Test Ledger -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">My Recent Activity</span>
                            <a href="{{ route('payments.index') }}" class="text-[10px] text-indigo-400 font-bold">Ledger →</a>
                        </div>

                        <div class="space-y-2">
                            @forelse($recentPayments as $rp)
                                <div class="p-2.5 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200/80 dark:border-slate-800/80 flex items-center justify-between text-xs">
                                    <div>
                                        <div class="font-mono font-bold text-slate-900 dark:text-white">₹{{ number_format((float)$rp->amount, 2) }}</div>
                                        <span class="text-[10px] text-slate-400">{{ $rp->created_at->format('d M, h:i A') }}</span>
                                    </div>
                                    <div>
                                        @if($rp->status->value === 'success')
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">SUCCESS</span>
                                        @elseif($rp->status->value === 'pending_verification')
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30">PENDING</span>
                                        @elseif($rp->status->value === 'rejected')
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30">REJECTED</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400">{{ strtoupper($rp->status->value) }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-[11px] text-slate-500 text-center py-3">No payments recorded yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
