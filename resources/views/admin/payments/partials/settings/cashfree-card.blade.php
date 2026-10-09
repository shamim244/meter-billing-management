{{-- 3. Cashfree Payment Gateway Credentials Card --}}
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

    {{-- Webhook URL Info Box --}}
    <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 text-xs space-y-1">
        <span class="text-[11px] font-bold text-slate-400 block uppercase">Cashfree Webhook Endpoint URL (Add in Cashfree Dashboard):</span>
        <div class="font-mono text-cyan-300 text-xs select-all bg-slate-950 p-2 rounded-lg border border-slate-800">
            {{ url('/webhooks/payments/cashfree') }}
        </div>
    </div>
</div>
