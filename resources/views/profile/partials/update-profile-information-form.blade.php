<section>
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-2xl bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-cyan-400 flex items-center justify-center text-lg font-black">
            👤
        </div>
        <div>
            <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">
                Profile Information
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Update your account's display name, official email, and phone number.
            </p>
        </div>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('patch')

        <!-- Full Name -->
        <div>
            <label for="name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Full Name
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <input id="name" 
                       name="name" 
                       type="text" 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm font-medium border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" 
                       value="{{ old('name', $user->name) }}" 
                       required 
                       autofocus 
                       autocomplete="name" />
            </div>
            <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
        </div>

        <!-- Official Email -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Official Email Address
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                </div>
                <input id="email" 
                       name="email" 
                       type="email" 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm font-medium border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" 
                       value="{{ old('email', $user->email) }}" 
                       required 
                       autocomplete="username" />
            </div>
            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2.5 p-3 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/80">
                    <p class="text-xs text-amber-800 dark:text-amber-300">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline font-bold text-amber-900 dark:text-amber-200 hover:text-amber-700 ml-1">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-bold text-xs text-emerald-600 dark:text-emerald-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Phone Number -->
        <div>
            <label for="phone" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Mobile Phone <span class="text-[10px] text-slate-400 font-normal lowercase">(optional)</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <input id="phone" 
                       name="phone" 
                       type="tel" 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm font-medium border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" 
                       value="{{ old('phone', $user->phone) }}" 
                       placeholder="e.g. 9876543210" />
            </div>
            <x-input-error class="mt-1.5" :messages="$errors->get('phone')" />
        </div>

        <!-- Submit Button & Feedback -->
        <div class="pt-3 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow-md shadow-brand-500/20 transition flex items-center gap-2">
                <span>💾 Save Profile Changes</span>
            </button>

            @if (session('status') === 'profile-updated')
                <span
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Profile updated successfully!
                </span>
            @endif
        </div>
    </form>
</section>
