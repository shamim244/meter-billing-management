{{-- 6. Bank Account Details (NEFT/IMPS) --}}
<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4 transition-opacity duration-200" :class="{'opacity-50 pointer-events-none': !bankTransferEnabled}">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
            <span>🏦</span> 6. Receiving Bank Account (NEFT / IMPS)
        </h2>
        <span :class="bankTransferEnabled ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border-rose-500/30'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border">
            <span x-text="bankTransferEnabled ? 'ACTIVE' : 'TURNED OFF'"></span>
        </span>
    </div>
    <p class="text-xs text-slate-400">Bank deposit instructions shown to billing agents during NEFT/IMPS transfers.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Bank Name <span class="text-rose-400">*</span></label>
            <input type="text" name="bank_name" value="{{ $settings['bank_name'] }}" required placeholder="e.g. State Bank of India" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Account Holder Name <span class="text-rose-400">*</span></label>
            <input type="text" name="bank_account_name" value="{{ $settings['bank_account_name'] }}" required placeholder="e.g. NBPDCL SaaS Billing Pvt Ltd" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Account Number <span class="text-rose-400">*</span></label>
            <input type="text" name="bank_account_number" value="{{ $settings['bank_account_number'] }}" required placeholder="e.g. 918273645019" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">IFSC Code <span class="text-rose-400">*</span></label>
            <input type="text" name="bank_ifsc" value="{{ $settings['bank_ifsc'] }}" required placeholder="e.g. SBIN0001234" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono uppercase">
        </div>
    </div>
</div>
