<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    {{-- Toggle 1: Online Gateway Master --}}
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
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="pg_enabled" value="1" x-model="pgEnabled" class="sr-only peer">
                <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
            </label>
        </div>
    </div>

    {{-- Toggle 2: Razorpay PG --}}
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

    {{-- Toggle 3: Cashfree PG --}}
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

    {{-- Toggle 4: Manual UPI QR --}}
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

    {{-- Toggle 5: Bank Transfer (NEFT/IMPS) --}}
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
