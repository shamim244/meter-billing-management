<!-- Password Input -->
<div>
    <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
        Password
    </label>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <input id="password" 
               :type="showPassword ? 'text' : 'password'" 
               name="password" 
               x-model="password"
               required 
               autocomplete="new-password"
               placeholder="Min. 8 characters"
               class="glass-input w-full pl-10 pr-11 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none @error('password') border-rose-500/80 ring-2 ring-rose-500/20 @enderror">
        <button type="button" 
                @click="showPassword = !showPassword" 
                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition"
                title="Toggle password visibility">
            <template x-if="!showPassword">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </template>
            <template x-if="showPassword">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
            </template>
        </button>
    </div>

    <!-- Reactive Password Strength Bar -->
    <div x-show="password.length > 0" class="mt-2" x-cloak>
        <div class="flex items-center gap-1.5 mb-1">
            <div class="h-1 flex-1 rounded-full transition-all duration-300" :class="strength >= 1 ? (strength === 1 ? 'bg-rose-500' : (strength === 2 ? 'bg-amber-500' : 'bg-emerald-400')) : 'bg-slate-800'"></div>
            <div class="h-1 flex-1 rounded-full transition-all duration-300" :class="strength >= 2 ? (strength === 2 ? 'bg-amber-500' : 'bg-emerald-400') : 'bg-slate-800'"></div>
            <div class="h-1 flex-1 rounded-full transition-all duration-300" :class="strength >= 3 ? 'bg-emerald-400' : 'bg-slate-800'"></div>
        </div>
        <div class="text-[10px] font-semibold flex items-center justify-between text-slate-400">
            <span>Strength:</span>
            <span :class="strength === 1 ? 'text-rose-400' : (strength === 2 ? 'text-amber-400' : 'text-emerald-400')" x-text="strength === 1 ? 'Weak' : (strength === 2 ? 'Medium' : 'Strong & Secure')"></span>
        </div>
    </div>

    @error('password')
        <p class="text-rose-400 text-xs font-semibold mt-1.5 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>

<!-- Confirm Password Input -->
<div>
    <label for="password_confirmation" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
        Confirm Password
    </label>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <input id="password_confirmation" 
               :type="showPassword ? 'text' : 'password'" 
               name="password_confirmation" 
               x-model="password_confirmation"
               required 
               autocomplete="new-password"
               placeholder="Repeat password"
               class="glass-input w-full pl-10 pr-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none @error('password_confirmation') border-rose-500/80 ring-2 ring-rose-500/20 @enderror">
    </div>
    @error('password_confirmation')
        <p class="text-rose-400 text-xs font-semibold mt-1.5 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>
