<!-- Referral Promo Code (Optional) -->
<div>
    <label for="referral_code" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5 flex items-center justify-between">
        <span>Referral Code <span class="text-[10px] text-slate-500 font-normal lowercase">(optional)</span></span>
        @if(request('ref'))
            <span class="text-[10px] text-emerald-400 font-medium">✨ Applied from Invite Link</span>
        @endif
    </label>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
            </svg>
        </div>
        <input id="referral_code" 
               type="text" 
               name="referral_code" 
               value="{{ old('referral_code', request('ref', '')) }}"
               placeholder="e.g. REF-A7X9K2"
               class="glass-input w-full pl-10 pr-4 py-2.5 rounded-xl text-sm font-mono uppercase text-white placeholder-slate-500 focus:outline-none uppercase">
    </div>
</div>
