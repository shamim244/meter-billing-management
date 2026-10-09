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
