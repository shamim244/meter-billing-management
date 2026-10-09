{{-- 2. Razorpay Gateway API Credentials Card --}}
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

    {{-- Webhook URL Info Box --}}
    <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 text-xs space-y-1">
        <span class="text-[11px] font-bold text-slate-400 block uppercase">Razorpay Webhook Endpoint URL (Add in Razorpay Dashboard):</span>
        <div class="font-mono text-cyan-300 text-xs select-all bg-slate-950 p-2 rounded-lg border border-slate-800">
            {{ url('/webhooks/payments/razorpay') }}
        </div>
    </div>
</div>
