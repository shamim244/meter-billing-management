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
