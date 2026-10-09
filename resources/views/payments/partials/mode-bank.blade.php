<!-- Option C: Bank Transfer Details -->
<div x-show="mode === 'bank_transfer'" class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-5">
    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
        <span>3️⃣</span> Bank Transfer Account Details (NEFT / IMPS)
    </h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs">
        <div>
            <span class="text-slate-400 block text-[11px]">Bank Name</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ $settings['bank_name'] }}</span>
        </div>

        <div>
            <span class="text-slate-400 block text-[11px]">Account Name</span>
            <span class="font-bold text-slate-900 dark:text-white">{{ $settings['bank_account_name'] }}</span>
        </div>

        <div>
            <span class="text-slate-400 block text-[11px]">Account Number</span>
            <div class="flex items-center gap-2">
                <span class="font-mono font-black text-slate-900 dark:text-cyan-300 text-sm">{{ $settings['bank_account_number'] }}</span>
                <button type="button" @click="copyText('{{ $settings['bank_account_number'] }}', 'bank')" class="px-2 py-0.5 bg-slate-200 dark:bg-slate-800 rounded text-[10px] font-bold">Copy</button>
            </div>
        </div>

        <div>
            <span class="text-slate-400 block text-[11px]">IFSC Code</span>
            <span class="font-mono font-bold text-slate-900 dark:text-white uppercase">{{ $settings['bank_ifsc'] }}</span>
        </div>
    </div>

    <!-- Bank Reference & Screenshot Inputs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100 dark:border-slate-800">
        <div>
            <label class="block text-xs font-bold text-slate-900 dark:text-white mb-1">
                Bank Reference / UTR Number <span class="text-rose-500">*</span>
            </label>
            <input type="text" name="bank_reference" x-model="bankReference" placeholder="e.g. N23874918237" class="w-full text-xs font-mono font-bold bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-slate-900 dark:text-white focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-900 dark:text-white mb-1">
                Bank Receipt / Slip Screenshot (Optional)
            </label>
            <input type="file" name="screenshot" accept="image/*" class="w-full text-xs bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl p-2 text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/60 dark:file:text-indigo-300">
        </div>
    </div>
</div>
