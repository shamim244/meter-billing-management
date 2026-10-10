<div>
    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Coupon Code <span class="text-rose-400">*</span></label>
    <input type="text" name="code" value="{{ old('code', $coupon->code ?? '') }}" required placeholder="e.g. WELCOME20 or SUMMERBONUS" class="w-full text-sm font-mono font-bold bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-white placeholder-slate-500 uppercase tracking-wider focus:ring-indigo-500 focus:border-indigo-500">
    <p class="text-[11px] text-slate-500 mt-1">Codes are stored uppercase and matched case-insensitively.</p>
</div>
