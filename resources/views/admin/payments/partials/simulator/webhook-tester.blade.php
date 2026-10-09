{{-- Tool 2: Webhook Payload & HMAC Signature Dispatcher --}}
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
                <select x-model="webhookGateway" @change="onGatewayChange()" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
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

        <button type="button" @click="triggerWebhook()" :disabled="webhookRunning" class="w-full py-2.5 bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-cyan-600/30 flex items-center justify-center gap-2">
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
