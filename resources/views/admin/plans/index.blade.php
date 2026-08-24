<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>📋</span> Subscription Plans Management
                </h1>
                <p class="text-sm text-slate-400 mt-1">Configure pricing tiers, included quotas, duration discounts, and overage rates.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.plans.overage_charges') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700/60 flex items-center gap-2">
                    <span>⚡</span> Overage Audit Logs
                </a>
                <a href="{{ route('admin.plans.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 flex items-center gap-2">
                    <span>➕</span> Create New Plan
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-300 text-xs font-semibold flex items-center gap-2">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-300 text-xs font-semibold flex items-center gap-2">
                <span>⚠️</span> {{ session('error') }}
            </div>
        @endif

        <!-- Metrics Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Active Subscribers</span>
                    <span class="p-2 bg-indigo-500/10 rounded-xl text-indigo-400 text-lg">👥</span>
                </div>
                <div class="text-2xl font-black text-white mt-2">{{ number_format($totalSubscribers) }}</div>
                <div class="text-[11px] text-slate-500 mt-1">Active billing agents</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Overage Revenue</span>
                    <span class="p-2 bg-emerald-500/10 rounded-xl text-emerald-400 text-lg">💰</span>
                </div>
                <div class="text-2xl font-black text-emerald-400 mt-2">₹{{ number_format($totalOverageRevenue, 2) }}</div>
                <div class="text-[11px] text-slate-500 mt-1">MRU & Consumer overages</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Locked MRUs</span>
                    <span class="p-2 bg-amber-500/10 rounded-xl text-amber-400 text-lg">🔒</span>
                </div>
                <div class="text-2xl font-black text-amber-400 mt-2">{{ number_format($lockedMrusCount) }}</div>
                <div class="text-[11px] text-slate-500 mt-1">Pending unlock / renewal</div>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Available Plans</span>
                    <span class="p-2 bg-purple-500/10 rounded-xl text-purple-400 text-lg">📦</span>
                </div>
                <div class="text-2xl font-black text-white mt-2">{{ $plans->whereNull('deleted_at')->count() }}</div>
                <div class="text-[11px] text-slate-500 mt-1">{{ $plans->whereNotNull('deleted_at')->count() }} deactivated</div>
            </div>
        </div>

        <!-- Plan Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($plans as $plan)
                <div class="bg-slate-900 border {{ $plan->trashed() ? 'border-rose-900/40 opacity-70' : 'border-slate-800' }} rounded-2xl p-6 flex flex-col justify-between shadow-xl relative overflow-hidden">
                    @if($plan->trashed())
                        <div class="absolute top-3 right-3 bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[9px] font-black uppercase px-2 py-0.5 rounded-full">
                            DEACTIVATED
                        </div>
                    @elseif($plan->is_active)
                        <div class="absolute top-3 right-3 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[9px] font-black uppercase px-2 py-0.5 rounded-full">
                            ACTIVE
                        </div>
                    @else
                        <div class="absolute top-3 right-3 bg-slate-800 text-slate-400 border border-slate-700 text-[9px] font-black uppercase px-2 py-0.5 rounded-full">
                            DRAFT / OFF
                        </div>
                    @endif

                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-black text-white">{{ $plan->name }}</h3>
                            <p class="text-xs text-slate-400 mt-1 min-h-[32px]">{{ $plan->description ?: 'Standard SaaS tier with quota allocations.' }}</p>
                        </div>

                        <!-- Quota Inclusions -->
                        <div class="bg-slate-950/80 rounded-xl p-3.5 border border-slate-800/80 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Included MRUs:</span>
                                <span class="font-bold text-white">{{ number_format($plan->included_mrus) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Included Consumers / Cycle:</span>
                                <span class="font-bold text-white">{{ number_format($plan->included_consumers) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs pt-1.5 border-t border-slate-800/60">
                                <span class="text-slate-400">Extra MRU Rate:</span>
                                <span class="font-bold text-amber-400">₹{{ number_format($plan->extra_mru_rate, 2) }}/MRU</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Extra Consumer Rate:</span>
                                <span class="font-bold text-amber-400">₹{{ number_format($plan->extra_consumer_rate, 2) }}/CA</span>
                            </div>
                        </div>

                        <!-- Duration Pricing Badges -->
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Duration Options</div>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($plan->durations as $dur)
                                    <span class="px-2.5 py-1 bg-slate-800/80 border border-slate-700/60 rounded-lg text-[11px] text-slate-300 font-medium">
                                        {{ $dur->duration_months }}M: <strong class="text-white">₹{{ number_format($dur->final_price) }}</strong>
                                        @if($dur->discount_percent > 0)
                                            <span class="text-[9px] text-emerald-400">({{ $dur->discount_percent }}% off)</span>
                                        @endif
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-500 italic">No duration pricing configured</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="pt-5 mt-5 border-t border-slate-800/80 flex items-center justify-between gap-2">
                        <a href="{{ route('admin.plans.agents', $plan) }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1">
                            <span>👥</span> {{ $plan->subscriptions_count }} Subscribers
                        </a>

                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.plans.durations.index', $plan) }}" class="px-2.5 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 rounded-lg text-xs font-semibold transition border border-indigo-500/30 flex items-center gap-1" title="Manage validity & pricing durations">
                                <span>⏳</span> Durations ({{ $plan->durations->count() }})
                            </a>
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold transition border border-slate-700/60">
                                ✏️ Edit
                            </a>
                            @if(!$plan->trashed())
                                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" onsubmit="return confirm('Deactivate this plan? Existing subscribers will remain active.');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 rounded-lg text-xs font-semibold transition border border-rose-500/20">
                                        Deactivate
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-16 bg-slate-900 border border-slate-800 rounded-2xl">
                    <span class="text-4xl">📋</span>
                    <h3 class="text-base font-bold text-white mt-3">No Subscription Plans Found</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Get started by creating your first subscription tier with customizable quotas and duration pricing.</p>
                    <a href="{{ route('admin.plans.create') }}" class="inline-block mt-4 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                        ➕ Create Plan
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</x-admin-layout>
