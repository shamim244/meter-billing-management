<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
        showMigrateModal: false,
        selectedUserId: null,
        selectedUserName: '',
        openMigrate(userId, userName) {
            this.selectedUserId = userId;
            this.selectedUserName = userName;
            this.showMigrateModal = true;
        }
    }">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.plans.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 mb-2">
                    ← Back to Plans
                </a>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>👥</span> Subscribed Agents — {{ $plan->name }}
                </h1>
                <p class="text-sm text-slate-400 mt-1">View locked subscriber snapshots and perform plan migrations.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-300 text-xs font-semibold flex items-center gap-2">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif

        <!-- Subscribers Table Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Subscribers Ledger</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Every row represents a legally binding locked pricing snapshot.</p>
                </div>
                <span class="px-3 py-1 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 rounded-lg text-xs font-bold">
                    {{ $subscriptions->total() }} Total Subscriptions
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-3.5 px-4 font-semibold">Agent / User</th>
                            <th class="py-3.5 px-4 font-semibold">Duration & Price Paid</th>
                            <th class="py-3.5 px-4 font-semibold">Locked MRU Quota</th>
                            <th class="py-3.5 px-4 font-semibold">Locked CA Quota</th>
                            <th class="py-3.5 px-4 font-semibold">Active Period</th>
                            <th class="py-3.5 px-4 font-semibold">Status</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($subscriptions as $sub)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white">{{ $sub->user?->name ?? 'Deleted User' }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $sub->user?->email }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-emerald-400">₹{{ number_format($sub->base_price_paid, 2) }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $sub->duration_months }} Month(s)</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-white">{{ $sub->included_mrus_locked }} MRUs</span>
                                    <div class="text-[10px] text-amber-400">₹{{ number_format($sub->extra_mru_rate_locked, 2) }}/extra</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-white">{{ $sub->included_consumers_locked }} CAs</span>
                                    <div class="text-[10px] text-amber-400">₹{{ number_format($sub->extra_consumer_rate_locked, 2) }}/extra</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-white">{{ $sub->billing_start?->format('d M Y') }}</div>
                                    <div class="text-[10px] text-slate-500">to {{ $sub->billing_end?->format('d M Y') }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($sub->isActive())
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                            ACTIVE
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-800 text-slate-400 border border-slate-700">
                                            {{ strtoupper($sub->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    @if($sub->user)
                                        <button type="button" @click="openMigrate({{ $sub->user->id }}, '{{ addslashes($sub->user->name) }}')" class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 rounded-lg text-xs font-semibold border border-indigo-500/30 transition">
                                            🔄 Migrate Plan
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-500 italic">
                                    No active subscribers currently on this plan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($subscriptions->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $subscriptions->links() }}
                </div>
            @endif
        </div>

        <!-- Plan Migration Modal -->
        <div x-show="showMigrateModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span>🔄</span> Migrate Agent Subscription
                    </h3>
                    <button type="button" @click="showMigrateModal = false" class="text-slate-400 hover:text-white text-lg font-bold">✕</button>
                </div>

                <p class="text-xs text-slate-400">
                    Migrating agent <strong class="text-white" x-text="selectedUserName"></strong> will deactivate their current plan and issue a new locked contract snapshot.
                </p>

                <form method="POST" action="{{ route('admin.plans.migrate_agent') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="user_id" :value="selectedUserId">

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Target Plan <span class="text-rose-400">*</span></label>
                        <select name="target_plan_id" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                            @foreach($allPlans as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->included_mrus }} MRUs / {{ $p->included_consumers }} CAs)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Duration <span class="text-rose-400">*</span></label>
                        <select name="duration_months" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                            <option value="1">1 Month</option>
                            <option value="2">2 Months</option>
                            <option value="3">3 Months</option>
                            <option value="6">6 Months</option>
                            <option value="12">12 Months</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                        <button type="button" @click="showMigrateModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-semibold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                            Confirm Migration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
