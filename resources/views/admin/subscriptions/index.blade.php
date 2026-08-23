<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
        showOverrideModal: false,
        subId: null,
        agentName: '',
        currentStatus: '',
        targetStatus: 'active',
        openOverride(id, name, status) {
            this.subId = id;
            this.agentName = name;
            this.currentStatus = status;
            this.targetStatus = status === 'suspended' ? 'active' : 'suspended';
            this.showOverrideModal = true;
        }
    }">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>🔄</span> Subscriptions & Lifecycle State Machine
                </h1>
                <p class="text-sm text-slate-400 mt-1">Manage Agent subscription lifecycles, grace periods, manual overrides, and read-only suspension rules.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.subscriptions.renewal_attempts') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700/60 flex items-center gap-2">
                    <span>🔁</span> Renewal Attempts
                </a>
                <a href="{{ route('admin.subscriptions.upgrade_logs') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700/60 flex items-center gap-2">
                    <span>⚖️</span> Proration Audit Logs
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-300 text-xs font-semibold flex items-center gap-2">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-300 text-xs font-semibold space-y-1">
                @foreach($errors->all() as $err)
                    <div>⚠️ {{ $err }}</div>
                @endforeach
            </div>
        @endif

        <!-- Metrics KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 backdrop-blur-md">
                <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider">
                    <span>Total Contracts</span>
                    <span>📑</span>
                </div>
                <div class="text-2xl font-black text-white mt-2">{{ number_format($counts['total']) }}</div>
            </div>

            <div class="bg-slate-900/60 border border-emerald-500/20 rounded-2xl p-4 backdrop-blur-md">
                <div class="flex items-center justify-between text-xs font-bold text-emerald-400 uppercase tracking-wider">
                    <span>Active</span>
                    <span>✅</span>
                </div>
                <div class="text-2xl font-black text-emerald-400 mt-2">{{ number_format($counts['active']) }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Full access</div>
            </div>

            <div class="bg-slate-900/60 border border-amber-500/20 rounded-2xl p-4 backdrop-blur-md">
                <div class="flex items-center justify-between text-xs font-bold text-amber-400 uppercase tracking-wider">
                    <span>Renewal Due</span>
                    <span>⏰</span>
                </div>
                <div class="text-2xl font-black text-amber-400 mt-2">{{ number_format($counts['renewal_due']) }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Full access + banner</div>
            </div>

            <div class="bg-slate-900/60 border border-orange-500/20 rounded-2xl p-4 backdrop-blur-md">
                <div class="flex items-center justify-between text-xs font-bold text-orange-400 uppercase tracking-wider">
                    <span>Grace Period</span>
                    <span>⏳</span>
                </div>
                <div class="text-2xl font-black text-orange-400 mt-2">{{ number_format($counts['grace_period']) }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Countdown active</div>
            </div>

            <div class="bg-slate-900/60 border border-rose-500/20 rounded-2xl p-4 backdrop-blur-md">
                <div class="flex items-center justify-between text-xs font-bold text-rose-400 uppercase tracking-wider">
                    <span>Suspended</span>
                    <span>🔒</span>
                </div>
                <div class="text-2xl font-black text-rose-400 mt-2">{{ number_format($counts['suspended']) }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Read-only mode</div>
            </div>
        </div>

        <!-- Global Settings Quick Bar -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                    <span>⚙️</span> Platform Grace Period Policy
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Sets platform-wide default grace period. Individual plans can override this.</p>
            </div>
            <form method="POST" action="{{ route('admin.subscriptions.update_settings') }}" class="flex items-center gap-3">
                @csrf
                <div class="flex items-center gap-2">
                    <label class="text-xs text-slate-300 font-medium">Default Days:</label>
                    <input type="number" min="0" max="90" name="default_grace_period_days" value="{{ $defaultGraceDays }}" class="w-16 text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-1.5 px-2.5 font-mono text-center">
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                    Save Default
                </button>
            </form>
        </div>

        <!-- Subscriptions Table Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-5 border-b border-slate-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Agent Subscriptions Ledger</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Real-time state machine tracking and access controls.</p>
                </div>

                <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="flex flex-wrap items-center gap-2.5">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search agent name/email..." class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-1.5 px-3 focus:ring-indigo-500 w-48">
                    <select name="lifecycle_status" onchange="this.form.submit()" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-1.5 px-3 focus:ring-indigo-500">
                        <option value="">All States</option>
                        <option value="active" {{ request('lifecycle_status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="renewal_due" {{ request('lifecycle_status') === 'renewal_due' ? 'selected' : '' }}>Renewal Due</option>
                        <option value="grace_period" {{ request('lifecycle_status') === 'grace_period' ? 'selected' : '' }}>Grace Period</option>
                        <option value="suspended" {{ request('lifecycle_status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-3.5 px-4 font-semibold">Agent / User</th>
                            <th class="py-3.5 px-4 font-semibold">Plan & Paid Rate</th>
                            <th class="py-3.5 px-4 font-semibold">Lifecycle State</th>
                            <th class="py-3.5 px-4 font-semibold">Billing Window</th>
                            <th class="py-3.5 px-4 font-semibold">Grace Window</th>
                            <th class="py-3.5 px-4 font-semibold">Auto-Renewal</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($subscriptions as $sub)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white">{{ $sub->user?->name ?? 'User #' . $sub->user_id }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $sub->user?->email }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-white">{{ $sub->plan?->name ?? 'Custom Tier' }}</span>
                                    <div class="text-[10px] text-emerald-400">₹{{ number_format($sub->base_price_paid, 2) }} / {{ $sub->duration_months }}M</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    @php
                                        $stateBadge = match($sub->lifecycle_status) {
                                            'active' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                            'renewal_due' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                            'grace_period' => 'bg-orange-500/20 text-orange-300 border-orange-500/30',
                                            'suspended' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                                            default => 'bg-slate-800 text-slate-400 border-slate-700',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $stateBadge }}">
                                        {{ str_replace('_', ' ', $sub->lifecycle_status) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-white">{{ $sub->billing_start?->format('d M Y') }}</div>
                                    <div class="text-[10px] text-slate-500">to {{ $sub->billing_end?->format('d M Y') }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($sub->grace_period_ends_at)
                                        <div class="text-amber-300 font-semibold">{{ $sub->grace_period_ends_at->format('d M Y, h:i A') }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $sub->grace_period_days }} Days Allowed</div>
                                    @else
                                        <span class="text-slate-500 text-[11px]">N/A (Active)</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($sub->auto_renewal_enabled)
                                        <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-md text-[10px] font-bold">
                                            ON (Wallet)
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 bg-slate-800 text-slate-400 border border-slate-700 rounded-md text-[10px]">
                                            Manual
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button type="button" @click="openOverride({{ $sub->id }}, '{{ addslashes($sub->user?->name ?? 'User') }}', '{{ $sub->lifecycle_status }}')" class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 rounded-lg text-xs font-semibold border border-indigo-500/30 transition">
                                        ⚡ Override State
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-500 italic">
                                    No agent subscriptions found matching your filters.
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

        <!-- State Override Modal -->
        <div x-show="showOverrideModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span>⚡</span> Force Subscription State Override
                    </h3>
                    <button type="button" @click="showOverrideModal = false" class="text-slate-400 hover:text-white text-lg font-bold">✕</button>
                </div>

                <p class="text-xs text-slate-400">
                    Target Agent: <strong class="text-white" x-text="agentName"></strong> (Current: <span class="font-bold text-amber-400 uppercase" x-text="currentStatus"></span>)
                </p>

                <form method="POST" :action="'/admin/subscriptions/' + subId + '/state-override'" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Target Lifecycle State <span class="text-rose-400">*</span></label>
                        <select name="target_status" x-model="targetStatus" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                            <option value="active">ACTIVE (Full access restored)</option>
                            <option value="renewal_due">RENEWAL_DUE (Renewal warning banner)</option>
                            <option value="grace_period">GRACE_PERIOD (Countdown warning banner)</option>
                            <option value="suspended">SUSPENDED (Read-only mode)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Mandatory Override Reason <span class="text-rose-400">*</span></label>
                        <textarea name="reason" required rows="3" placeholder="Explain the support reason or business authorization for this state change..." class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                        <button type="button" @click="showOverrideModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-semibold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                            Confirm Override
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
