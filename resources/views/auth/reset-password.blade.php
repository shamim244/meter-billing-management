<x-guest-layout>
    <div x-data="{
        password: '',
        password_confirmation: '',
        showPassword: false,
        isSubmitting: false
    }" class="w-full max-w-md mx-auto">
        <!-- Glassmorphism Main Card -->
        <div class="glass-panel rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden">
            
            <!-- Top Subtle Gradient Border Line -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand-500 via-indigo-500 to-cyan-400"></div>

            <!-- Card Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-brand-500/10 border border-brand-500/20 text-brand-400 mb-3 shadow-inner">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight">Reset Password</h1>
                <p class="text-xs text-slate-400 mt-1">Set a new secure password for your account</p>
            </div>

            <form method="POST" action="{{ route('password.store') }}" @submit="isSubmitting = true" class="space-y-4">
                @csrf

                <!-- Password Reset Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                        Official Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </div>
                        <input id="email" 
                               type="email" 
                               name="email" 
                               value="{{ old('email', $request->email) }}" 
                               required 
                               autofocus 
                               autocomplete="username"
                               placeholder="operator@nbpdcl-saas.com"
                               class="glass-input w-full pl-10 pr-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none @error('email') border-rose-500/80 ring-2 ring-rose-500/20 @enderror">
                    </div>
                    @error('email')
                        <p class="text-rose-400 text-xs font-semibold mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                        New Password
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
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition">
                            <template x-if="!showPassword">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </template>
                            <template x-if="showPassword">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                            </template>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-rose-400 text-xs font-semibold mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                        Confirm New Password
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

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" 
                            :disabled="isSubmitting"
                            class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-brand-500 to-indigo-500 hover:from-brand-600 hover:to-indigo-600 text-white font-bold text-sm shadow-lg shadow-brand-500/20 transition-all duration-200 active:scale-[0.99] flex items-center justify-center gap-2 disabled:opacity-50">
                        <span x-show="!isSubmitting">🔒 Reset & Update Password</span>
                        <span x-show="isSubmitting" class="flex items-center gap-2" x-cloak>
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Updating...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
