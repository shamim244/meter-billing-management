<x-user-panel-layout>
    <x-slot name="header">
        Subscription & Storage Allocation
    </x-slot>

    <div class="space-y-8">
        <!-- Storage Quota Status Hero -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-brand-50 dark:bg-brand-950/80 text-brand-700 dark:text-cyan-300 border border-brand-200/60 dark:border-brand-800/60">
                            Current Plan: {{ strtoupper($user->plan_tier ?? 'Free Starter') }}
                        </span>
                        <span class="text-xs text-slate-400">•</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Auto-renewing Lifetime Free</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Physical Storage & Quota Allocation</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                        Track your physical electricity bill PDF disk storage. When approaching limit, use the Cycle Storage Cleaner to purge completed cycle PDFs while keeping consumer readings 100% preserved.
                    </p>
                </div>

                <div class="bg-slate-50 dark:bg-slate-950/60 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 min-w-[260px] space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500 dark:text-slate-400 font-semibold">Disk Quota Used</span>
                        <span class="font-mono font-black text-brand-600 dark:text-cyan-400">{{ $stats['storage_percent'] }}%</span>
                    </div>
                    <div class="w-full h-3 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-brand-500 to-cyan-400 rounded-full transition-all duration-300" style="width: {{ min(100, $stats['storage_percent']) }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400">
                        <span>{{ round($stats['storage_used_bytes'] / (1024 * 1024), 2) }} MB used</span>
                        <span>{{ round($stats['storage_limit_bytes'] / (1024 * 1024)) }} MB limit</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plan Comparison & Upgrade Tiers -->
        <div>
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Available Subscription Tiers</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Scale your NBPDCL billing capacity as your consumer portfolio expands.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                @foreach($plans as $plan)
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border flex flex-col justify-between relative transition duration-200 {{ $plan['id'] === ($user->plan_tier ?? 'free') ? 'border-brand-500 dark:border-cyan-400 shadow-xl ring-2 ring-brand-500/20' : 'border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md' }}">
                        @if($plan['id'] === ($user->plan_tier ?? 'free'))
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-600 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full shadow-md">
                                Current Active Plan
                            </div>
                        @elseif($plan['id'] === 'pro')
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full shadow-md">
                                ★ {{ $plan['badge'] }}
                            </div>
                        @endif

                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $plan['name'] }}</h3>
                            </div>

                            <div class="mt-4 flex items-baseline gap-1">
                                <span class="text-3xl font-black text-slate-900 dark:text-white tracking-tight font-mono">{{ $plan['price'] }}</span>
                                <span class="text-xs text-slate-400 font-medium">/ {{ $plan['period'] }}</span>
                            </div>

                            <div class="mt-4 p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800/80 text-xs space-y-1">
                                <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <span>💾</span> {{ $plan['storage'] }}
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                    ⚡ {{ $plan['concurrency'] }}
                                </div>
                            </div>

                            <div class="mt-6 space-y-2.5">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Features included:</span>
                                @foreach($plan['features'] as $feature)
                                    <div class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300">
                                        <span class="text-emerald-500 font-bold shrink-0">✓</span>
                                        <span>{{ $feature }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-8 pt-4 border-t border-slate-100 dark:border-slate-800">
                            @if($plan['id'] === ($user->plan_tier ?? 'free'))
                                <button type="button" disabled class="w-full py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 font-bold text-xs cursor-default text-center">
                                    Current Active Plan
                                </button>
                            @else
                                <a href="{{ route('payments.create', ['purpose' => 'direct_subscription', 'amount' => $plan['id'] === 'pro' ? 499 : 1499]) }}" class="w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs flex items-center justify-center gap-1 shadow-md shadow-brand-500/20 transition text-center">
                                    <span>Upgrade & Pay ({{ $plan['price'] }})</span>
                                    <span>→</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Ledger Preservation FAQs -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                <span>💡</span> Storage & Ledger Protection Guarantee
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                    <strong class="text-slate-800 dark:text-slate-200 block mb-1">How does cycle purging protect my disk space?</strong>
                    Once a monthly billing cycle is completed and verified, you can safely purge its heavy physical PDF files via the PDF Document Manager. Consumer readings, units, dues, and remarks remain 100% preserved in the database.
                </div>
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800/80">
                    <strong class="text-slate-800 dark:text-slate-200 block mb-1">What happens if I reach my quota limit?</strong>
                    If you reach 100% quota, new bill downloads are paused to prevent disk errors. You can either purge older cycles in 1 click or request a quota upgrade.
                </div>
            </div>
        </div>
    </div>
</x-user-panel-layout>
