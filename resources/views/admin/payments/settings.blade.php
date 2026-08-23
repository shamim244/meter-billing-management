<x-admin-layout>
    <x-slot name="header">
        Payment Gateway & Channel Settings
    </x-slot>

    <div x-data="{
        pgEnabled: {{ $settings['pg_enabled'] ? 'true' : 'false' }},
        cashfreeEnabled: {{ $settings['cashfree_enabled'] ? 'true' : 'false' }},
        razorpayEnabled: {{ $settings['razorpay_enabled'] ? 'true' : 'false' }},
        manualUpiEnabled: {{ $settings['manual_upi_enabled'] ? 'true' : 'false' }},
        bankTransferEnabled: {{ $settings['bank_transfer_enabled'] ? 'true' : 'false' }},
        activePgDriver: '{{ $settings['active_pg_driver'] }}'
    }" class="space-y-6">

        <!-- Top Navigation Tabs -->
        @include('admin.payments.nav')

        <!-- Top Flash Alerts -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold flex items-center justify-between shadow-lg">
                <span>✅ {{ session('success') }}</span>
                <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400">Configure active payment methods, gateway credentials, and business settlement accounts.</span>
            <span class="text-xs text-slate-500">Last updated: {{ now()->format('d M Y, h:i A') }}</span>
        </div>

        <form action="{{ route('admin.payments.settings.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- 1. Master Payment Channel ON / OFF Switchboard -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-900 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                            <span>🎛️</span> 1. Payment Methods & Gateway Toggles (Turn ON / OFF)
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Toggle switches to enable or disable specific payment channels for all billing agents.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    
                    <!-- Toggle 1: Online Gateway Master -->
                    <div :class="pgEnabled ? 'border-indigo-500/50 bg-slate-900' : 'border-slate-800/80 bg-slate-950 opacity-75'" class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">⚡</span>
                                <div>
                                    <div class="font-bold text-xs text-white">Online PG Master</div>
                                    <div class="text-[10px] text-slate-400">Instant Gateway Checkout</div>
                                </div>
                            </div>
                            <span :class="pgEnabled ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700'" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border">
                                <span x-text="pgEnabled ? 'ON' : 'OFF'"></span>
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-800/60">
                            <span class="text-[11px] text-slate-400 font-medium">Enable Online Gateways</span>
                            <!-- Custom iOS/Tailwind Toggle Switch -->
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="pg_enabled" value="1" x-model="pgEnabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Toggle 2: Razorpay PG -->
                    <div :class="razorpayEnabled && pgEnabled ? 'border-indigo-500/50 bg-slate-900' : 'border-slate-800/80 bg-slate-950 opacity-75'" class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">💳</span>
                                <div>
                                    <div class="font-bold text-xs text-white">Razorpay Standard</div>
                                    <div class="text-[10px] text-slate-400">Razorpay JS & SDK</div>
                                </div>
                            </div>
                            <span :class="razorpayEnabled && pgEnabled ? 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30' : 'bg-slate-800 text-slate-400 border-slate-700'" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border">
                                <span x-text="razorpayEnabled && pgEnabled ? 'ACTIVE' : 'OFF'"></span>
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-800/60">
                            <span class="text-[11px] text-slate-400 font-medium">Razorpay Provider</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="razorpay_enabled" value="1" x-model="razorpayEnabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Toggle 3: Cashfree PG -->
                    <div :class="cashfreeEnabled && pgEnabled ? 'border-cyan-500/50 bg-slate-900' : 'border-slate-800/80 bg-slate-950 opacity-75'" class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">💳</span>
                                <div>
                                    <div class="font-bold text-xs text-white">Cashfree Payments</div>
                                    <div class="text-[10px] text-slate-400">Cashfree PG SDK (v3)</div>
                                </div>
                            </div>
                            <span :class="cashfreeEnabled && pgEnabled ? 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30' : 'bg-slate-800 text-slate-400 border-slate-700'" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border">
                                <span x-text="cashfreeEnabled && pgEnabled ? 'ACTIVE' : 'OFF'"></span>
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-800/60">
                            <span class="text-[11px] text-slate-400 font-medium">Cashfree Provider</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="cashfree_enabled" value="1" x-model="cashfreeEnabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Toggle 4: Manual UPI QR -->
                    <div :class="manualUpiEnabled ? 'border-purple-500/50 bg-slate-900' : 'border-slate-800/80 bg-slate-950 opacity-75'" class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">📱</span>
                                <div>
                                    <div class="font-bold text-xs text-white">Manual UPI Transfer</div>
                                    <div class="text-[10px] text-slate-400">QR Code + UTR Verification</div>
                                </div>
                            </div>
                            <span :class="manualUpiEnabled ? 'bg-purple-500/20 text-purple-400 border-purple-500/30' : 'bg-slate-800 text-slate-400 border-slate-700'" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border">
                                <span x-text="manualUpiEnabled ? 'ON' : 'OFF'"></span>
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-800/60">
                            <span class="text-[11px] text-slate-400 font-medium">Enable Manual UPI</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="manual_upi_enabled" value="1" x-model="manualUpiEnabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Toggle 5: Bank Transfer (NEFT/IMPS) -->
                    <div :class="bankTransferEnabled ? 'border-blue-500/50 bg-slate-900' : 'border-slate-800/80 bg-slate-950 opacity-75'" class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">🏦</span>
                                <div>
                                    <div class="font-bold text-xs text-white">Bank Transfer (NEFT)</div>
                                    <div class="text-[10px] text-slate-400">Direct Account Deposit</div>
                                </div>
                            </div>
                            <span :class="bankTransferEnabled ? 'bg-blue-500/20 text-blue-400 border-blue-500/30' : 'bg-slate-800 text-slate-400 border-slate-700'" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border">
                                <span x-text="bankTransferEnabled ? 'ON' : 'OFF'"></span>
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-800/60">
                            <span class="text-[11px] text-slate-400 font-medium">Enable Bank Transfer</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="bank_transfer_enabled" value="1" x-model="bankTransferEnabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Primary Online PG Driver Selector -->
                <div x-show="pgEnabled" class="pt-4 border-t border-slate-900">
                    <label class="block text-xs font-bold text-slate-300 mb-2">Default Active Online Gateway</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-lg">
                        <label :class="activePgDriver === 'razorpay' ? 'border-indigo-500 bg-indigo-950/30 text-indigo-200' : 'border-slate-800 bg-slate-900 text-slate-400'" class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer hover:border-slate-700 transition">
                            <input type="radio" name="active_pg_driver" value="razorpay" x-model="activePgDriver" class="text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <div class="font-bold text-xs text-white">Razorpay Standard</div>
                                <div class="text-[10px] text-slate-400">Primary instant checkout modal</div>
                            </div>
                        </label>

                        <label :class="activePgDriver === 'cashfree' ? 'border-cyan-500 bg-cyan-950/30 text-cyan-200' : 'border-slate-800 bg-slate-900 text-slate-400'" class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer hover:border-slate-700 transition">
                            <input type="radio" name="active_pg_driver" value="cashfree" x-model="activePgDriver" class="text-cyan-600 focus:ring-cyan-500">
                            <div>
                                <div class="font-bold text-xs text-white">Cashfree Payments</div>
                                <div class="text-[10px] text-slate-400">Primary instant checkout dropin</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 2. Razorpay Gateway API Credentials Card -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4 transition-opacity duration-200" :class="{'opacity-50 pointer-events-none': !pgEnabled || !razorpayEnabled}">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>💳</span> 2. Razorpay PG API Credentials
                    </h2>
                    <div class="flex items-center gap-2">
                        <span x-show="pgEnabled && razorpayEnabled && activePgDriver === 'razorpay'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                            PRIMARY DRIVER
                        </span>
                        <span :class="pgEnabled && razorpayEnabled ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border-rose-500/30'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border">
                            <span x-text="pgEnabled && razorpayEnabled ? 'ONLINE' : 'TURNED OFF'"></span>
                        </span>
                    </div>
                </div>
                <p class="text-xs text-slate-400">Obtain Key ID and Secret from Razorpay Dashboard → Settings → API Keys.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Razorpay Key ID <span class="text-rose-400">*</span></label>
                        <input type="text" name="razorpay_key_id" value="{{ $settings['razorpay_key_id'] }}" placeholder="rzp_test_xxxxxxxx" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Razorpay Key Secret <span class="text-rose-400">*</span></label>
                        <input type="password" name="razorpay_key_secret" value="{{ $settings['razorpay_key_secret'] }}" placeholder="••••••••••••••••" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Razorpay Webhook Secret (Optional)</label>
                        <input type="text" name="razorpay_webhook_secret" value="{{ $settings['razorpay_webhook_secret'] }}" placeholder="e.g. rzp_wh_secret_xxxx" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                    </div>
                </div>

                <!-- Webhook URL Info Box -->
                <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 text-xs space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 block uppercase">Razorpay Webhook Endpoint URL (Add in Razorpay Dashboard):</span>
                    <div class="font-mono text-cyan-300 text-xs select-all bg-slate-950 p-2 rounded-lg border border-slate-800">
                        {{ url('/webhooks/payments/razorpay') }}
                    </div>
                </div>
            </div>

            <!-- 3. Cashfree Payment Gateway Credentials Card -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4 transition-opacity duration-200" :class="{'opacity-50 pointer-events-none': !pgEnabled || !cashfreeEnabled}">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>💳</span> 3. Cashfree PG API Credentials
                    </h2>
                    <div class="flex items-center gap-2">
                        <span x-show="pgEnabled && cashfreeEnabled && activePgDriver === 'cashfree'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                            PRIMARY DRIVER
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $settings['cashfree_environment'] === 'production' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                            {{ strtoupper($settings['cashfree_environment']) }} MODE
                        </span>
                        <span :class="pgEnabled && cashfreeEnabled ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border-rose-500/30'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border">
                            <span x-text="pgEnabled && cashfreeEnabled ? 'ONLINE' : 'TURNED OFF'"></span>
                        </span>
                    </div>
                </div>
                <p class="text-xs text-slate-400">Enter your Cashfree Merchant credentials from the Cashfree Dashboard.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Cashfree App ID / Client ID <span class="text-rose-400">*</span></label>
                        <input type="text" name="cashfree_app_id" value="{{ $settings['cashfree_app_id'] }}" placeholder="e.g. 123456xxxxxxxx" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Cashfree Secret Key <span class="text-rose-400">*</span></label>
                        <input type="password" name="cashfree_secret_key" value="{{ $settings['cashfree_secret_key'] }}" placeholder="••••••••••••••••" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Environment Mode</label>
                        <select name="cashfree_environment" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-semibold">
                            <option value="sandbox" {{ $settings['cashfree_environment'] === 'sandbox' ? 'selected' : '' }}>🧪 Sandbox (Test / Development)</option>
                            <option value="production" {{ $settings['cashfree_environment'] === 'production' ? 'selected' : '' }}>🚀 Production (Live Payments)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Webhook Secret (Optional)</label>
                        <input type="text" name="cashfree_webhook_secret" value="{{ $settings['cashfree_webhook_secret'] }}" placeholder="e.g. cf_wh_sec_xxxx" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                    </div>
                </div>

                <!-- Webhook URL Info Box -->
                <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 text-xs space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 block uppercase">Cashfree Webhook Endpoint URL (Add in Cashfree Dashboard):</span>
                    <div class="font-mono text-cyan-300 text-xs select-all bg-slate-950 p-2 rounded-lg border border-slate-800">
                        {{ url('/webhooks/payments/cashfree') }}
                    </div>
                </div>
            </div>

            <!-- 4. Minimum Amount Rule -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>💵</span> 4. Minimum Payment Amount Threshold
                </h2>

                <div class="max-w-md">
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Minimum Allowed Payment (₹) <span class="text-rose-400">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 text-slate-400 font-bold text-xs">₹</span>
                        <input type="number" step="1" min="1" name="min_amount" value="{{ $settings['min_amount'] }}" required class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white pl-8 pr-4 py-2.5 focus:ring-indigo-500 focus:border-indigo-500 font-mono font-bold">
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Billing Agents cannot initiate top-up or checkout below this threshold.</span>
                </div>
            </div>

            <!-- 5. Manual UPI Business Details -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4 transition-opacity duration-200" :class="{'opacity-50 pointer-events-none': !manualUpiEnabled}">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>📱</span> 5. Receiving UPI Account (Manual UPI)
                    </h2>
                    <span :class="manualUpiEnabled ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border-rose-500/30'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border">
                        <span x-text="manualUpiEnabled ? 'ACTIVE' : 'TURNED OFF'"></span>
                    </span>
                </div>
                <p class="text-xs text-slate-400">The UPI ID & QR Code shown to billing agents when they choose Manual UPI mode.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Business UPI VPA / ID <span class="text-rose-400">*</span></label>
                        <input type="text" name="business_upi_id" value="{{ $settings['business_upi_id'] }}" required placeholder="e.g. nbpdcl.billing@sbi" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Payee Business Name <span class="text-rose-400">*</span></label>
                        <input type="text" name="business_upi_name" value="{{ $settings['business_upi_name'] }}" required placeholder="e.g. NBPDCL SaaS Billing" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- 6. Bank Account Details (NEFT/IMPS) -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4 transition-opacity duration-200" :class="{'opacity-50 pointer-events-none': !bankTransferEnabled}">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>🏦</span> 6. Receiving Bank Account (NEFT / IMPS)
                    </h2>
                    <span :class="bankTransferEnabled ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border-rose-500/30'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border">
                        <span x-text="bankTransferEnabled ? 'ACTIVE' : 'TURNED OFF'"></span>
                    </span>
                </div>
                <p class="text-xs text-slate-400">Bank deposit instructions shown to billing agents during NEFT/IMPS transfers.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Bank Name <span class="text-rose-400">*</span></label>
                        <input type="text" name="bank_name" value="{{ $settings['bank_name'] }}" required placeholder="e.g. State Bank of India" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Account Holder Name <span class="text-rose-400">*</span></label>
                        <input type="text" name="bank_account_name" value="{{ $settings['bank_account_name'] }}" required placeholder="e.g. NBPDCL SaaS Billing Pvt Ltd" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Account Number <span class="text-rose-400">*</span></label>
                        <input type="text" name="bank_account_number" value="{{ $settings['bank_account_number'] }}" required placeholder="e.g. 918273645019" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">IFSC Code <span class="text-rose-400">*</span></label>
                        <input type="text" name="bank_ifsc" value="{{ $settings['bank_ifsc'] }}" required placeholder="e.g. SBIN0001234" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono uppercase">
                    </div>
                </div>
            </div>

            <!-- 7. Wallet System & Alert Thresholds -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>👛</span> 7. Wallet & Balance Alert Thresholds
                    </h2>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        CONFIGURABLE
                    </span>
                </div>
                <p class="text-xs text-slate-400">Configure balance alert triggers and minimum top-up requirements across the platform.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Low Balance Alert Threshold (₹) <span class="text-rose-400">*</span></label>
                        <input type="number" step="1" min="0" name="wallet_low_balance_threshold" value="{{ $settings['wallet_low_balance_threshold'] ?? 200.0 }}" required placeholder="200" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        <span class="text-[10px] text-slate-500 mt-1 block">Fires WalletLowBalanceEvent when agent balance drops below this amount (default ₹200.00).</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Minimum Payment / Top-Up Amount (₹) <span class="text-rose-400">*</span></label>
                        <input type="number" step="1" min="1" name="min_amount" value="{{ $settings['min_amount'] }}" required placeholder="100" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        <span class="text-[10px] text-slate-500 mt-1 block">Minimum allowed transaction amount across all payment checkout forms.</span>
                    </div>
                </div>
            </div>

            <!-- Submit Action -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.payments.index') }}" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 flex items-center gap-2">
                    <span>💾</span> Save All Payment Settings
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
