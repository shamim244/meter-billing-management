<x-app-layout>
    <!-- Razorpay Standard Checkout JS -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <!-- Cashfree Web JS SDK v3 -->
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>

    <div x-data="{
        mode: '{{ $settings['pg_enabled'] ? 'pg' : ($settings['manual_upi_enabled'] ? 'manual_upi' : 'bank_transfer') }}',
        amount: {{ $pricingDetails['final_amount'] }},
        activePgDriver: '{{ $settings['active_pg_driver'] }}',
        utrNumber: '',
        bankReference: '',
        copiedUpi: false,
        copiedBank: false,
        isSubmitting: false,
        errorMessage: null,
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
            let am = this.amount;
            let upiString = `upi://pay?pa=${upiId}&pn=${name}&am=${am}&cu=INR&tn=NBPDCL_Subscription_{{ $plan->id }}`;
            return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(upiString)}`;
        },
        async handleCheckout(e) {
            if (this.mode !== 'pg') {
                this.isSubmitting = true;
                return true;
            }

            e.preventDefault();
            this.isSubmitting = true;
            this.errorMessage = null;

            try {
                const response = await fetch('{{ route('subscription.purchase.process', ['plan' => $plan->id, 'duration' => $duration->id], false) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        mode: 'pg',
                        action_mode: '{{ $pricingDetails['action_mode'] }}'
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
                        description: `Subscription: {{ $plan->name }} ({{ $duration->duration_months }} mo)`,
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

            <!-- Header & Navigation -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('user-panel.subscription') }}" class="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition">
                        ← Back to Plans
                    </a>
                    <div>
                        <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>⭐</span> Subscription Purchase Confirmation
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Review plan quotas, duration discount, and choose your payment method.</p>
                    </div>
                </div>

                <div class="hidden sm:block">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        Fixed Pricing Guarantee
                    </span>
                </div>
            </div>

            <!-- Error Alerts -->
            <div x-show="errorMessage" x-cloak class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                <span x-text="'❌ ' + errorMessage"></span>
                <button type="button" @click="errorMessage = null" class="text-rose-600 dark:text-rose-400 font-bold">✕</button>
            </div>

            @if($errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold space-y-1">
                    <div class="font-bold">❌ Please correct the following errors:</div>
                    <ul class="list-disc list-inside pl-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(isset($downgradeEligibility) && !$downgradeEligibility['eligible'])
                <div class="p-5 rounded-3xl bg-amber-50 dark:bg-amber-950/50 border-2 border-amber-300 dark:border-amber-800 space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">⚠️</span>
                        <div>
                            <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">Active MRU Quota Exceeded for this Plan</h3>
                            <p class="text-xs text-amber-800 dark:text-amber-300 mt-1">
                                The target plan (<strong>{{ $plan->name }}</strong>) includes <strong>{{ $downgradeEligibility['new_plan_quota'] }}</strong> MRU(s). You currently have <strong>{{ $downgradeEligibility['active_mrus_count'] }}</strong> active MRU workspaces.
                                Please lock or delete <strong>{{ $downgradeEligibility['excess_mrus'] }}</strong> MRU(s) to proceed with this plan downgrade.
                            </p>
                        </div>
                    </div>

                    <!-- Quick Lock Buttons for Active MRUs -->
                    <div class="space-y-2 pt-3 border-t border-amber-200 dark:border-amber-800">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                            Lock an MRU workspace to free quota:
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($downgradeEligibility['active_mrus'] as $mruItem)
                                <div class="flex items-center justify-between p-3 rounded-2xl bg-white dark:bg-slate-900 border border-amber-200 dark:border-slate-800 shadow-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-lg text-xs font-mono font-bold bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-cyan-300 border border-blue-200 dark:border-blue-800">{{ $mruItem->code }}</span>
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $mruItem->name }}</span>
                                    </div>
                                    <form method="POST" action="{{ route('mrus.lock', $mruItem) }}" onsubmit="return confirm('Lock MRU {{ $mruItem->code }} to free quota?');">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition flex items-center gap-1 shadow-xs">
                                            <span>🔒 Lock</span>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-2 border-t border-amber-200 dark:border-amber-800 flex items-center justify-between">
                        <span class="text-xs text-slate-500">Need to manage or delete MRUs?</span>
                        <a href="{{ route('mrus.index') }}" class="text-xs font-bold text-blue-600 dark:text-cyan-400 hover:underline flex items-center gap-1">
                            <span>🗂️ Go to MRU Workspace Management →</span>
                        </a>
                    </div>
                </div>
            @endif

            <!-- 1. Plan & Pricing Summary Card (Server-Derived, Fixed) -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                {{ $plan->name }}
                            </span>
                            <span class="text-xs text-slate-400">•</span>
                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                                {{ $duration->formatted_duration }} Duration
                            </span>
                            @if($duration->discount_percent > 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    {{ $duration->discount_percent }}% OFF
                                </span>
                            @endif
                        </div>

                        <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ $plan->name }} Plan Activation
                        </h2>

                        @if($plan->description)
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xl">
                                {{ $plan->description }}
                            </p>
                        @endif

                        <!-- Quota Inclusions -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                                <div class="text-[10px] text-slate-400 uppercase font-bold">Included MRUs</div>
                                <div class="text-sm font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ number_format($plan->included_mrus) }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                                <div class="text-[10px] text-slate-400 uppercase font-bold">Consumers / Cycle</div>
                                <div class="text-sm font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ number_format($plan->included_consumers) }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                                <div class="text-[10px] text-slate-400 uppercase font-bold">Extra MRU Rate</div>
                                <div class="text-sm font-black text-slate-900 dark:text-white font-mono mt-0.5">₹{{ number_format($duration->extra_mru_rate ?? $plan->extra_mru_rate, 2) }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                                <div class="text-[10px] text-slate-400 uppercase font-bold">Extra CA Rate</div>
                                <div class="text-sm font-black text-slate-900 dark:text-white font-mono mt-0.5">₹{{ number_format($duration->extra_consumer_rate ?? $plan->extra_consumer_rate, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Summary Display Box (Fixed, Non-Editable) -->
                    <div class="bg-gradient-to-br from-indigo-900/40 to-slate-900/60 dark:bg-slate-950 p-6 rounded-3xl border border-indigo-500/20 dark:border-slate-800 min-w-[280px] space-y-4">
                        <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">Amount Due</div>

                        @if($pricingDetails['action_type'] === 'upgrade' && $pricingDetails['proration'])
                            <div class="space-y-1.5 text-xs text-slate-300 border-b border-slate-800 pb-3">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">New Plan Cost (Prorated):</span>
                                    <span class="font-mono font-bold">₹{{ number_format($pricingDetails['proration']['new_plan_cost'], 2) }}</span>
                                </div>
                                <div class="flex justify-between text-emerald-400">
                                    <span>Current Plan Credit:</span>
                                    <span class="font-mono font-bold">-₹{{ number_format($pricingDetails['proration']['old_plan_credit'], 2) }}</span>
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    {{ $pricingDetails['proration']['days_remaining'] }} of {{ $pricingDetails['proration']['total_days_in_cycle'] }} cycle days remaining.
                                </div>
                            </div>
                        @elseif($pricingDetails['discount_percent'] > 0)
                            <div class="space-y-1 text-xs text-slate-400 border-b border-slate-800 pb-2">
                                <div class="flex justify-between">
                                    <span>Standard Price:</span>
                                    <span class="line-through font-mono">₹{{ number_format($plan->base_price * $duration->duration_months, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-amber-400">
                                    <span>Duration Discount:</span>
                                    <span class="font-bold">{{ $duration->discount_percent }}% OFF</span>
                                </div>
                            </div>
                        @endif

                        <div>
                            <div class="text-3xl font-black text-white font-mono tracking-tight">
                                ₹{{ number_format($pricingDetails['final_amount'], 2) }}
                            </div>
                            <span class="text-[11px] text-slate-400 block mt-0.5">Fixed total amount for this checkout</span>
                        </div>

                        <!-- Wallet Option Shortcut -->
                        @if($walletBalance >= $pricingDetails['final_amount'])
                            <form method="POST" action="{{ route('subscription.subscribe_wallet') }}">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <input type="hidden" name="duration_id" value="{{ $duration->id }}">
                                <input type="hidden" name="action_mode" value="{{ $pricingDetails['action_mode'] }}">
                                <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-1.5">
                                    <span>👛 Pay from Wallet Balance (₹{{ number_format($walletBalance, 2) }})</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 2. Payment Method Form (Scope to Fixed Amount) -->
            <form action="{{ route('subscription.purchase.process', ['plan' => $plan->id, 'duration' => $duration->id]) }}" method="POST" enctype="multipart/form-data" @submit="handleCheckout($event)" class="space-y-6">
                @csrf
                <input type="hidden" name="action_mode" value="{{ $pricingDetails['action_mode'] }}">

                <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                            <span>💳</span> Select Payment Mode
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Choose how you wish to pay the fixed amount of ₹{{ number_format($pricingDetails['final_amount'], 2) }}.</p>
                    </div>

                    <!-- Payment Mode Tabs -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @if($settings['pg_enabled'])
                            <label :class="mode === 'pg' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between">
                                <div class="flex items-center justify-between mb-2">
                                    <input type="radio" name="mode" value="pg" x-model="mode" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">Instant</span>
                                </div>
                                <div>
                                    <div class="font-bold text-xs">⚡ Online Gateway</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">UPI, QR, Cards, NetBanking</div>
                                </div>
                            </label>
                        @endif

                        @if($settings['manual_upi_enabled'])
                            <label :class="mode === 'manual_upi' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between">
                                <div class="flex items-center justify-between mb-2">
                                    <input type="radio" name="mode" value="manual_upi" x-model="mode" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300">Manual</span>
                                </div>
                                <div>
                                    <div class="font-bold text-xs">📱 Direct UPI Transfer</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">GPay, PhonePe, Paytm QR</div>
                                </div>
                            </label>
                        @endif

                        @if($settings['bank_transfer_enabled'])
                            <label :class="mode === 'bank_transfer' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300'" class="p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between">
                                <div class="flex items-center justify-between mb-2">
                                    <input type="radio" name="mode" value="bank_transfer" x-model="mode" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300">NEFT/IMPS</span>
                                </div>
                                <div>
                                    <div class="font-bold text-xs">🏦 Bank Transfer</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Direct NEFT, RTGS, IMPS</div>
                                </div>
                            </label>
                        @endif
                    </div>

                    <!-- Payment Mode Details -->
                    <!-- 1. Online PG -->
                    <div x-show="mode === 'pg'" x-cloak class="p-5 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/60 space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🔒</span>
                            <div>
                                <h3 class="text-xs font-bold text-indigo-900 dark:text-indigo-200">Automated Secure Checkout</h3>
                                <p class="text-[11px] text-indigo-700 dark:text-indigo-300">Your subscription will activate instantly upon payment completion.</p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Manual UPI -->
                    <div x-show="mode === 'manual_upi'" x-cloak class="space-y-4 pt-2">
                        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center gap-6">
                            <div class="bg-white p-3 rounded-2xl border border-slate-200 shadow-sm shrink-0">
                                <img :src="qrCodeUrl" alt="UPI QR Code" class="w-36 h-36 rounded-lg">
                                <span class="text-[9px] text-slate-400 text-center block mt-1">Scan using any UPI App</span>
                            </div>

                            <div class="space-y-3 w-full text-xs">
                                <div>
                                    <span class="text-slate-400 font-medium block text-[11px]">Payable Amount:</span>
                                    <span class="text-lg font-black text-slate-900 dark:text-white font-mono">₹{{ number_format($pricingDetails['final_amount'], 2) }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-medium block text-[11px]">Official Business UPI ID:</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <code class="px-2.5 py-1 rounded-lg bg-slate-200 dark:bg-slate-800 font-mono font-bold text-indigo-600 dark:text-indigo-400 text-xs">{{ $settings['business_upi_id'] }}</code>
                                        <button type="button" @click="copyText('{{ $settings['business_upi_id'] }}', 'upi')" class="px-2 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950 border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 text-[10px] font-bold">
                                            <span x-text="copiedUpi ? 'Copied! ✓' : 'Copy'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    12-Digit UPI Reference Number (UTR) <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="utr_number" x-model="utrNumber" maxlength="100" placeholder="e.g. 423874910284" class="w-full text-xs font-mono font-bold p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Payment Screenshot Receipt <span class="text-slate-400 font-normal">(Optional)</span>
                                </label>
                                <input type="file" name="screenshot" accept="image/*" class="w-full text-xs file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-300 text-slate-500">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Bank Transfer -->
                    <div x-show="mode === 'bank_transfer'" x-cloak class="space-y-4 pt-2">
                        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs space-y-2">
                            <div class="font-bold text-slate-900 dark:text-white mb-2 flex items-center justify-between">
                                <span>🏛 Official Beneficiary Bank Details</span>
                                <button type="button" @click="copyText('Account: {{ $settings['bank_account_number'] ?? '' }} | IFSC: {{ $settings['bank_ifsc'] ?? ($settings['bank_ifsc_code'] ?? '') }}', 'bank')" class="px-2 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950 border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 text-[10px] font-bold">
                                    <span x-text="copiedBank ? 'Copied Details! ✓' : 'Copy All'"></span>
                                </button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-[11px]">
                                <div><span class="text-slate-400">Account Name:</span> <strong class="text-slate-800 dark:text-slate-200">{{ $settings['bank_account_name'] ?? '' }}</strong></div>
                                <div><span class="text-slate-400">Account Number:</span> <strong class="font-mono text-slate-800 dark:text-slate-200">{{ $settings['bank_account_number'] ?? '' }}</strong></div>
                                <div><span class="text-slate-400">IFSC Code:</span> <strong class="font-mono text-slate-800 dark:text-slate-200">{{ $settings['bank_ifsc'] ?? ($settings['bank_ifsc_code'] ?? '') }}</strong></div>
                                <div><span class="text-slate-400">Bank Name:</span> <strong class="text-slate-800 dark:text-slate-200">{{ $settings['bank_name'] ?? '' }}</strong></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Bank Reference / UTR / Transaction ID <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="bank_reference" x-model="bankReference" maxlength="100" placeholder="e.g. IMPS/NEFT Ref Number" class="w-full text-xs font-mono font-bold p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Bank Receipt Screenshot <span class="text-slate-400 font-normal">(Optional)</span>
                                </label>
                                <input type="file" name="screenshot" accept="image/*" class="w-full text-xs file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-300 text-slate-500">
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="submit" :disabled="isSubmitting" class="w-full py-3.5 px-6 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-black text-sm shadow-lg shadow-brand-500/20 transition flex items-center justify-center gap-2 disabled:opacity-50">
                            <span x-show="!isSubmitting">Proceed to Pay ₹{{ number_format($pricingDetails['final_amount'], 2) }} →</span>
                            <span x-show="isSubmitting" x-cloak>Processing Checkout...</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
