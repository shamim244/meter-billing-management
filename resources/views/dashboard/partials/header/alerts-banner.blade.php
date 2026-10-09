{{-- Flash Alerts & Subscription Onboarding Banner --}}
<div>
    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-sm mb-3">
            <div class="flex items-center gap-2">
                <span>✅</span> {{ session('success') }}
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:opacity-75">✕</button>
        </div>
    @endif

    @if(session('info'))
        <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 text-blue-800 dark:text-cyan-300 text-xs font-semibold flex items-center justify-between shadow-sm mb-3">
            <div class="flex items-center gap-2">
                <span>ℹ️</span> {{ session('info') }}
            </div>
            <button @click="$el.parentElement.remove()" class="text-blue-600 dark:text-cyan-400 hover:opacity-75">✕</button>
        </div>
    @endif

    @if(session('warning'))
        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs font-semibold flex items-center justify-between shadow-sm mb-3">
            <div class="flex items-center gap-2">
                <span>⚠️</span> {{ session('warning') }}
            </div>
            <button @click="$el.parentElement.remove()" class="text-amber-600 dark:text-amber-400 hover:opacity-75">✕</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm mb-3">
            <div class="flex items-center gap-2">
                <span>❌</span> {{ session('error') }}
            </div>
            <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75">✕</button>
        </div>
    @endif

    <!-- Subscription Onboarding Banner (When user has no active plan) -->
    @if(!$activeSubscription)
        <div class="p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-gradient-to-r from-amber-500/10 via-indigo-500/5 to-transparent border border-amber-300 dark:border-amber-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm mb-3">
            <div class="flex items-start sm:items-center gap-3.5">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg shrink-0 font-black">
                    ⚡
                </div>
                <div>
                    <div class="text-sm font-black text-slate-900 dark:text-white flex flex-wrap items-center gap-2">
                        <span>Get Started with a Subscription Plan</span>
                        <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">100% Free Plan Available</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                        You do not have an active subscription yet. Activate our <strong>Free Starter Tier</strong> (1 MRU & 500 Consumers) with 1 click to create cycles and download bills immediately.
                    </p>
                </div>
            </div>
            <a href="{{ route('user-panel.subscription') }}" class="shrink-0 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/20 transition active:scale-95 text-center">
                <span>⚡ Activate Free Plan / Choose Tier</span>
                <span>→</span>
            </a>
        </div>
    @endif
</div>
