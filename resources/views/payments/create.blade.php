<x-app-layout>
    <!-- Razorpay Standard Checkout JS -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <!-- Cashfree Web JS SDK v3 -->
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>

    <div x-data="{
        mode: '{{ $settings['pg_enabled'] ? 'pg' : ($settings['manual_upi_enabled'] ? 'manual_upi' : 'bank_transfer') }}',
        purpose: 'wallet_topup',
        amount: {{ $presetAmount }},
        minAmount: {{ $settings['min_amount'] }},
        activePgDriver: '{{ $settings['active_pg_driver'] }}',
        utrNumber: '',
        bankReference: '',
        copiedUpi: false,
        copiedBank: false,
        isSubmitting: false,
        errorMessage: null,
        couponCodeInput: '',
        appliedCoupon: null,
        couponError: null,
        isValidatingCoupon: false,
        setAmount(val) {
            this.amount = val;
            if (this.appliedCoupon) {
                this.validateCoupon();
            }
        },
        async validateCoupon() {
            if (!this.couponCodeInput.trim()) return;
            this.isValidatingCoupon = true;
            this.couponError = null;
            try {
                const response = await fetch('{{ route('payments.validate-coupon', [], false) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        code: this.couponCodeInput.trim(),
                        amount: this.amount,
                        action_type: 'topup_bonus'
                    })
                });

                const data = await response.json();
                if (data.valid) {
                    this.appliedCoupon = data;
                    this.couponError = null;
                } else {
                    this.couponError = data.message || 'Invalid coupon code for this recharge amount.';
                    this.appliedCoupon = null;
                }
            } catch (err) {
                this.couponError = 'Failed to validate coupon code.';
            } finally {
                this.isValidatingCoupon = false;
            }
        },
        removeCoupon() {
            this.couponCodeInput = '';
            this.appliedCoupon = null;
            this.couponError = null;
        },
        copyText(text, type) {
            navigator.clipboard.writeText(text);
            if (type === 'upi') {
                this.copiedUpi = true;
                setTimeout(() => this.copiedUpi = false, 2000);
            } else if (type === 'bank') {
                this.copiedBank = true;
                setTimeout(() => this.copiedBank = false, 2000);
            }
        },
        get qrCodeUrl() {
            let upiId = '{{ $settings['business_upi_id'] }}';
            let name = encodeURIComponent('{{ $settings['business_upi_name'] }}');
            let am = this.amount || this.minAmount;
            let upiString = `upi://pay?pa=${upiId}&pn=${name}&am=${am}&cu=INR&tn=NBPDCL_Wallet_Topup`;
            return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(upiString)}`;
        },
        async handleCheckout(e) {
            if (this.mode !== 'pg') {
                this.isSubmitting = true;
                return true; // standard multi-part submit for manual uploads
            }

            e.preventDefault();
            this.isSubmitting = true;
            this.errorMessage = null;

            try {
                const response = await fetch('{{ route('payments.store', [], false) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        mode: 'pg',
                        purpose: 'wallet_topup',
                        amount: this.amount,
                        coupon_code: this.appliedCoupon ? this.appliedCoupon.code : null
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Failed to initialize payment gateway checkout.');
                }

                // 1. Razorpay Checkout Flow
                if (data.order && data.order.gateway === 'razorpay') {
                    const _this = this;
                    const options = {
                        key: data.order.key,
                        amount: data.order.amount_paise,
                        currency: 'INR',
                        name: data.order.name || 'NBPDCL SaaS Billing',
                        description: data.order.description || 'Wallet Top-up',
                        order_id: data.order.order_id,
                        prefill: {
                            name: data.order.customer_name,
                            email: data.order.customer_email,
                            contact: data.order.customer_phone || ''
                        },
                        notes: data.order.notes || {},
                        theme: data.order.theme || { color: '#4f46e5' },
                        handler: function (resp) {
                            window.location.href = `{{ route('payments.verify', [], false) }}?razorpay_payment_id=${encodeURIComponent(resp.razorpay_payment_id)}&razorpay_order_id=${encodeURIComponent(resp.razorpay_order_id)}&razorpay_signature=${encodeURIComponent(resp.razorpay_signature)}`;
                        },
                        modal: {
                            ondismiss: function () {
                                _this.isSubmitting = false;
                            }
                        }
                    };

                    if (window.Razorpay) {
                        const rzp = new Razorpay(options);
                        rzp.on('payment.failed', function (resp) {
                            _this.isSubmitting = false;
                            _this.errorMessage = resp.error.description || 'Payment was declined.';
                        });
                        rzp.open();
                    } else {
                        throw new Error('Razorpay checkout library failed to load.');
                    }
                    return;
                }

                // 2. Cashfree Drop Checkout Flow
                if (data.order && data.order.gateway === 'cashfree') {
                    if (window.Cashfree) {
                        const cashfree = Cashfree({ mode: data.order.environment === 'production' ? 'production' : 'sandbox' });
                        cashfree.checkout({
                            paymentSessionId: data.order.payment_session_id,
                            redirectTarget: '_self'
                        });
                    } else {
                        window.location.href = '{{ route('payments.index', [], false) }}';
                    }
                    return;
                }

                window.location.href = '{{ route('payments.index', [], false) }}';
            } catch (err) {
                this.isSubmitting = false;
                this.errorMessage = err.message || 'Payment initiation failed. Please try again.';
            }
        }
    }" class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('payments.index') }}" class="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition">
                        ← Back to Ledger
                    </a>
                    <div>
                        <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>👛</span> Add Funds / Wallet Top-Up
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Add prepaid balance to your Agent Wallet for automated cycle audits, quota overage, and subscription renewals.</p>
                    </div>
                </div>

                <div>
                    <a href="{{ route('payments.sandbox') }}" class="px-4 py-2 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <span>🧪</span> Open Sandbox Test Mode →
                    </a>
                </div>
            </div>

            <!-- Client-side Error Alert -->
            <div x-show="errorMessage" x-cloak class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                <span x-text="'❌ ' + errorMessage"></span>
                <button type="button" @click="errorMessage = null" class="text-rose-600 dark:text-rose-400 font-bold">✕</button>
            </div>

            <!-- Server Flash Errors -->
            @if($errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <span>❌</span> Please correct the following errors:
                    </div>
                    <ul class="list-disc list-inside pl-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <span>❌ {{ session('error') }}</span>
                    <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 font-bold">✕</button>
                </div>
            @endif

            <form action="{{ route('payments.store') }}" method="POST" enctype="multipart/form-data" @submit="handleCheckout($event)" class="space-y-6">
                @csrf
                <input type="hidden" name="purpose" value="wallet_topup">

                <!-- 1. Top-Up Amount Card -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-5">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>1️⃣</span> Top-Up Amount
                    </h2>

                    <!-- Amount Input & Quick Presets -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Enter Amount (Minimum ₹<span x-text="minAmount"></span>)
                        </label>
                        <div class="relative max-w-sm">
                            <span class="absolute left-4 top-3 text-slate-400 font-bold text-base">₹</span>
                            <input type="number" step="1" :min="minAmount" name="amount" x-model.number="amount" required class="w-full text-base font-black font-mono pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Quick Presets -->
                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            <span class="text-[11px] text-slate-400 font-medium">Quick Select:</span>
                            <button type="button" @click="setAmount(500)" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 transition">₹500</button>
                            <button type="button" @click="setAmount(1000)" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 transition">₹1,000</button>
                            <button type="button" @click="setAmount(2500)" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 transition">₹2,500</button>
                            <button type="button" @click="setAmount(5000)" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 transition">₹5,000</button>
                        </div>
                    </div>

                    <!-- Coupon Code Card (Recharge Bonus Promo) -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span>🎟️</span>
                                <span>Have a Top-Up Bonus Promo Code?</span>
                            </span>
                            <template x-if="appliedCoupon">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                    Bonus Activated
                                </span>
                            </template>
                        </div>

                        <template x-if="!appliedCoupon">
                            <div class="space-y-1.5">
                                <div class="flex gap-2 max-w-sm">
                                    <input type="text" x-model="couponCodeInput" @keydown.enter.prevent="validateCoupon()" placeholder="e.g. EXTRAWALLET" class="flex-1 text-xs font-mono font-bold uppercase bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-indigo-500">
                                    <button type="button" @click="validateCoupon()" :disabled="isValidatingCoupon || !couponCodeInput.trim()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer">
                                        <span x-show="!isValidatingCoupon">Apply</span>
                                        <span x-show="isValidatingCoupon" x-cloak>...</span>
                                    </button>
                                </div>
                                <p x-show="couponError" x-text="couponError" class="text-[11px] text-rose-500 font-semibold"></p>
                            </div>
                        </template>

                        <template x-if="appliedCoupon">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/80">
                                <div>
                                    <div class="font-mono font-bold text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                                        <span>🎁</span>
                                        <span x-text="appliedCoupon.code"></span>
                                        <span>(+₹<span x-text="appliedCoupon.discount_or_bonus_amount.toLocaleString('en-IN')"></span> Bonus Credit)</span>
                                    </div>
                                    <div class="text-[10px] text-emerald-700 dark:text-emerald-400 mt-0.5" x-text="appliedCoupon.message"></div>
                                </div>
                                <button type="button" @click="removeCoupon()" class="text-xs text-rose-500 hover:text-rose-600 font-bold px-2 py-1 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition">
                                    Remove
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <input type="hidden" name="coupon_code" :value="appliedCoupon ? appliedCoupon.code : ''">

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

                <!-- 3. Dynamic Mode Details & Proof Submission -->
                
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

                <!-- Option B: Manual UPI Details -->
                <div x-show="mode === 'manual_upi'" class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-5">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>3️⃣</span> Manual UPI Transfer Details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                        <!-- QR Code -->
                        <div class="flex flex-col items-center justify-center p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 text-center space-y-2">
                            <img :src="qrCodeUrl" alt="Scan to Pay" class="w-44 h-44 rounded-xl border border-slate-200 dark:border-slate-700 bg-white p-2">
                            <span class="text-[11px] font-bold text-slate-500">Scan via GPay, PhonePe, Paytm, BHIM</span>
                        </div>

                        <!-- VPA & Payee Details -->
                        <div class="space-y-4 text-xs">
                            <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 space-y-1">
                                <span class="text-[11px] text-slate-400 block">Receiving UPI ID</span>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono font-bold text-slate-900 dark:text-cyan-300 text-sm">{{ $settings['business_upi_id'] }}</span>
                                    <button type="button" @click="copyText('{{ $settings['business_upi_id'] }}', 'upi')" class="px-2.5 py-1 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 rounded-lg text-[11px] font-bold transition">
                                        <span x-text="copiedUpi ? '✅ Copied!' : 'Copy'"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
                                <span class="text-[11px] text-slate-400 block">Payee Name</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $settings['business_upi_name'] }}</span>
                            </div>

                            <div class="text-[11px] text-slate-500 dark:text-slate-400 italic">
                                * Pay the exact amount (<span class="font-bold text-slate-900 dark:text-white" x-text="'₹' + amount"></span>) using your UPI app, then enter the 12-digit UTR below.
                            </div>
                        </div>
                    </div>

                    <!-- UTR & Screenshot Inputs -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div>
                            <label class="block text-xs font-bold text-slate-900 dark:text-white mb-1">
                                UPI 12-Digit UTR / Transaction ID <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="utr_number" x-model="utrNumber" placeholder="e.g. 423987129034" class="w-full text-xs font-mono font-bold bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-slate-900 dark:text-white focus:ring-indigo-500">
                            <span class="text-[10px] text-slate-400 mt-1 block">Found in your payment receipt on GPay/PhonePe</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-900 dark:text-white mb-1">
                                Payment Screenshot (Optional)
                            </label>
                            <input type="file" name="screenshot" accept="image/*" class="w-full text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl p-2 text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/60 dark:file:text-indigo-300">
                        </div>
                    </div>
                </div>

                <!-- Option C: Bank Transfer Details -->
                <div x-show="mode === 'bank_transfer'" class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-5">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>3️⃣</span> Bank Transfer Account Details (NEFT / IMPS)
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[11px]">Bank Name</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $settings['bank_name'] }}</span>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[11px]">Account Name</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $settings['bank_account_name'] }}</span>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[11px]">Account Number</span>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-black text-slate-900 dark:text-cyan-300 text-sm">{{ $settings['bank_account_number'] }}</span>
                                <button type="button" @click="copyText('{{ $settings['bank_account_number'] }}', 'bank')" class="px-2 py-0.5 bg-slate-200 dark:bg-slate-800 rounded text-[10px] font-bold">Copy</button>
                            </div>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[11px]">IFSC Code</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white uppercase">{{ $settings['bank_ifsc'] }}</span>
                        </div>
                    </div>

                    <!-- Bank Reference & Screenshot Inputs -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div>
                            <label class="block text-xs font-bold text-slate-900 dark:text-white mb-1">
                                Bank Reference / UTR Number <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="bank_reference" x-model="bankReference" placeholder="e.g. N23874918237" class="w-full text-xs font-mono font-bold bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-slate-900 dark:text-white focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-900 dark:text-white mb-1">
                                Bank Receipt / Slip Screenshot (Optional)
                            </label>
                            <input type="file" name="screenshot" accept="image/*" class="w-full text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl p-2 text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/60 dark:file:text-indigo-300">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('payments.index') }}" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold transition">
                        Cancel
                    </a>

                    <button type="submit" :disabled="isSubmitting || amount < minAmount || (mode === 'manual_upi' && !utrNumber) || (mode === 'bank_transfer' && !bankReference)" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-500/25 transition flex items-center gap-2">
                        <span x-show="!isSubmitting">⚡</span>
                        <span x-show="isSubmitting" class="inline-block animate-spin">⏳</span>
                        <span x-text="mode === 'pg' ? (isSubmitting ? 'Opening Gateway...' : 'Proceed to Payment') : (isSubmitting ? 'Submitting...' : 'Submit for Verification')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
