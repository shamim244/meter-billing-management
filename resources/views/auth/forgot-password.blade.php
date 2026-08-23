<x-guest-layout>
    <div x-data="{ isSubmitting: false }" class="w-full max-w-md mx-auto">
        <!-- Glassmorphism Main Card -->
        <div class="glass-panel rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden">
            
            <!-- Top Subtle Gradient Border Line -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 via-brand-500 to-cyan-400"></div>

            <!-- Card Header -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mb-3 shadow-inner">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight">Forgot Password?</h1>
                <p class="text-xs text-slate-400 mt-1">Enter your registered email and we'll send you a password reset link.</p>
            </div>

            <!-- Session Status Alert -->
            @if (session('status'))
                <div class="mb-6 p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" @submit="isSubmitting = true" class="space-y-4">
                @csrf

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
                               value="{{ old('email') }}" 
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

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" 
                            :disabled="isSubmitting"
                            class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-brand-500 hover:from-amber-600 hover:to-brand-600 text-white font-bold text-sm shadow-lg shadow-amber-500/20 transition-all duration-200 active:scale-[0.99] flex items-center justify-center gap-2 disabled:opacity-50">
                        <span x-show="!isSubmitting">📨 Send Reset Link</span>
                        <span x-show="isSubmitting" class="flex items-center gap-2" x-cloak>
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Sending...
                        </span>
                    </button>
                </div>
            </form>

            <!-- Back to Login Link -->
            <div class="mt-8 pt-6 border-t border-slate-800/80 text-center">
                <a href="{{ route('login') }}" class="text-xs font-bold text-slate-400 hover:text-white transition inline-flex items-center gap-1.5">
                    ← Back to Sign In
                </a>
            </div>

        </div>
    </div>
</x-guest-layout>
