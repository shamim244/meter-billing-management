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
