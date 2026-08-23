<x-admin-layout>
    <x-slot name="header">
        Payment Gateway Testing & Sandbox Simulator
    </x-slot>

    <div x-data="{
        webhookGateway: 'razorpay',
        webhookEvent: 'payment.captured',
        webhookAmount: 1500,
        webhookRunning: false,
        webhookResult: null,
        async triggerWebhook() {
            this.webhookRunning = true;
            this.webhookResult = null;
            try {
                const res = await fetch('{{ route('admin.payments.simulator.webhook') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        gateway: this.webhookGateway,
                        event_type: this.webhookEvent,
                        amount: this.webhookAmount
                    })
                });
                const data = await res.json();
                this.webhookResult = data;
            } catch (err) {
                this.webhookResult = { error: err.message || 'Webhook simulation failed.' };
            } finally {
                this.webhookRunning = false;
            }
        }
    }" class="space-y-6">

        <!-- Top Navigation Tabs -->
        @include('admin.payments.nav')

        <!-- Flash Alerts -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold flex items-center justify-between shadow-lg">
                <span>✅ {{ session('success') }}</span>
                <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold flex items-center justify-between shadow-lg">
                <span>❌ {{ session('error') }}</span>
                <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
            </div>
        @endif

        <!-- Banner Info -->
        <div class="p-5 rounded-2xl bg-indigo-950/40 border border-indigo-500/30 text-xs text-indigo-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="font-bold text-white flex items-center gap-2 text-sm">
                    <span>🧪</span> Developer Sandbox & Gateway Testing Console
                </div>
                <p class="text-slate-400 text-xs">
                    Test end-to-end payment flows, mock checkout success/failures, dispatch HMAC-signed webhooks, and populate verification queues without live API credentials.
                </p>
            </div>

            <!-- Quick Seed Action Button -->
            <form action="{{ route('admin.payments.simulator.seed') }}" method="POST" class="shrink-0">
                @csrf
                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 flex items-center gap-2">
                    <span>✨</span> Populate Sample Demo Records
                </button>
            </form>
        </div>

        <!-- Testing Grid: Tool 1 & Tool 2 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Tool 1: Instant Gateway Checkout Simulator -->
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

            <!-- Tool 2: Webhook Payload & HMAC Signature Dispatcher -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-5">
                <div class="border-b border-slate-900 pb-3">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>📡</span> 2. Webhook Event & HMAC Signature Tester
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Send a real, cryptographically signed webhook payload to test endpoint validation.</p>
                </div>

                <div class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Target Gateway</label>
                            <select x-model="webhookGateway" @change="webhookEvent = (webhookGateway === 'razorpay' ? 'payment.captured' : 'PAYMENT_SUCCESS_WEBHOOK')" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                                <option value="razorpay">Razorpay Webhooks</option>
                                <option value="cashfree">Cashfree Webhooks</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Webhook Event Type</label>
                            <select x-model="webhookEvent" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                                <template x-if="webhookGateway === 'razorpay'">
                                    <optgroup label="Razorpay Events">
                                        <option value="payment.captured">payment.captured (Success)</option>
                                        <option value="payment.failed">payment.failed (Failure)</option>
                                        <option value="subscription.charged">subscription.charged</option>
                                    </optgroup>
                                </template>
                                <template x-if="webhookGateway === 'cashfree'">
                                    <optgroup label="Cashfree Events">
                                        <option value="PAYMENT_SUCCESS_WEBHOOK">PAYMENT_SUCCESS_WEBHOOK</option>
                                        <option value="PAYMENT_FAILED_WEBHOOK">PAYMENT_FAILED_WEBHOOK</option>
                                        <option value="SUBSCRIPTION_CHARGE_FAILED">SUBSCRIPTION_CHARGE_FAILED</option>
                                    </optgroup>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Event Amount (₹)</label>
                        <input type="number" step="1" min="1" x-model.number="webhookAmount" class="w-full text-xs font-mono font-bold bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    </div>

                    <button type="button" @click="triggerWebhook" :disabled="webhookRunning" class="w-full py-2.5 bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-cyan-600/30 flex items-center justify-center gap-2">
                        <span x-show="!webhookRunning">⚡</span>
                        <span x-show="webhookRunning" class="animate-spin">⏳</span>
                        <span x-text="webhookRunning ? 'Dispatching & Validating Signature...' : 'Dispatch Signed Webhook to Handler'"></span>
                    </button>

                    <!-- Webhook Live Response Box -->
                    <div x-show="webhookResult" x-cloak class="p-3 bg-slate-900 rounded-xl border border-slate-800 space-y-2">
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-400 uppercase">
                            <span>Handler Response Output</span>
                            <span :class="webhookResult?.status === 'ok' ? 'text-emerald-400' : 'text-rose-400'" x-text="webhookResult?.status === 'ok' ? 'HTTP 200 OK (Verified)' : 'Error'"></span>
                        </div>
                        <pre class="text-[11px] font-mono text-cyan-300 p-2 bg-slate-950 rounded-lg overflow-x-auto max-h-40" x-text="JSON.stringify(webhookResult, null, 2)"></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Diagnostics & System Health -->
        <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <span>🩺</span> System Diagnostics & Gateway SDK Status
            </h3>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
                    <span class="text-slate-500 text-[10px] block">PHP Runtime</span>
                    <span class="font-bold text-white font-mono">v{{ $diagnostics['php_version'] }}</span>
                </div>

                <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
                    <span class="text-slate-500 text-[10px] block">Razorpay PHP SDK</span>
                    <span class="font-bold {{ $diagnostics['razorpay_sdk_available'] ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $diagnostics['razorpay_sdk_available'] ? '✓ Loaded (v2.9.3)' : '✕ Missing' }}
                    </span>
                </div>

                <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
                    <span class="text-slate-500 text-[10px] block">Cashfree PG SDK</span>
                    <span class="font-bold {{ $diagnostics['cashfree_sdk_available'] ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $diagnostics['cashfree_sdk_available'] ? '✓ Loaded (v6.0.0)' : '✕ Missing' }}
                    </span>
                </div>

                <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
                    <span class="text-slate-500 text-[10px] block">HMAC SHA256 Crypto</span>
                    <span class="font-bold {{ $diagnostics['openssl_installed'] ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $diagnostics['openssl_installed'] ? '✓ OpenSSL Active' : '✕ Inactive' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Recent Simulated Transactions Feed -->
        <div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm space-y-3 p-5">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>📋</span> Recent Payment Activity Feed
                </h3>
                <a href="{{ route('admin.payments.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold">
                    View Master Ledger →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-[10px] uppercase text-slate-400 border-b border-slate-800 font-bold">
                        <tr>
                            <th class="py-2.5 px-3">Payment ID</th>
                            <th class="py-2.5 px-3">User / Agent</th>
                            <th class="py-2.5 px-3">Mode</th>
                            <th class="py-2.5 px-3">Amount</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3 text-right">Audit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono">
                        @forelse($recentPayments as $rp)
                            <tr class="hover:bg-slate-900/40">
                                <td class="py-2.5 px-3 text-white font-bold">#{{ $rp->id }}</td>
                                <td class="py-2.5 px-3 font-sans text-slate-300">{{ $rp->user->name ?? 'User #' . $rp->user_id }}</td>
                                <td class="py-2.5 px-3 font-sans">
                                    @if($rp->mode->value === 'pg')
                                        <span class="text-cyan-400 font-bold">⚡ Online PG</span>
                                    @elseif($rp->mode->value === 'manual_upi')
                                        <span class="text-purple-400 font-bold">📱 Manual UPI</span>
                                    @else
                                        <span class="text-blue-400 font-bold">🏦 Bank Transfer</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-white font-black">₹{{ number_format((float)$rp->amount, 2) }}</td>
                                <td class="py-2.5 px-3 font-sans">
                                    @if($rp->status->value === 'success')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">SUCCESS</span>
                                    @elseif($rp->status->value === 'pending_verification')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">PENDING</span>
                                    @elseif($rp->status->value === 'rejected')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">REJECTED</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">{{ strtoupper($rp->status->value) }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-sans">
                                    <a href="{{ route('admin.payments.show', $rp->id) }}" class="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-[11px] font-semibold transition">
                                        Inspect →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-500 font-sans">No payment records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
