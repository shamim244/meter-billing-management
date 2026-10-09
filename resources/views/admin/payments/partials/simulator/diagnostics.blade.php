{{-- Diagnostics & System Health --}}
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
