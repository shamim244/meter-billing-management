<x-admin-layout :title="'Payment #' . $payment->id . ' Details'">
    <div class="space-y-6">
        <!-- Top Navigation Tabs -->
        @include('admin.payments.nav')

        <!-- Top Navigation -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.payments.index') }}" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                    ← Back to Queue
                </a>
                <div>
                    <h1 class="text-xl font-black text-white flex items-center gap-2">
                        <span>💳</span> Payment #{{ $payment->id }}
                    </h1>
                    <p class="text-xs text-slate-400">Created on {{ $payment->created_at->format('d M Y, h:i:s A') }}</p>
                </div>
            </div>

            <!-- Status Badge -->
            <div>
                @if($payment->status->value === 'pending_verification')
                    <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 inline-flex items-center gap-1.5">
                        <span>⏳</span> Pending Admin Verification
                    </span>
                @elseif($payment->status->value === 'success')
                    <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 inline-flex items-center gap-1.5">
                        <span>✅</span> Payment Successful
                    </span>
                @elseif($payment->status->value === 'rejected')
                    <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40 inline-flex items-center gap-1.5">
                        <span>❌</span> Payment Rejected
                    </span>
                @else
                    <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        {{ $payment->status->label() }}
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Payment & Billing Agent Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Payment Overview Card -->
                <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400">Transaction Details</h2>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                        <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block mb-1">Amount</span>
                            <span class="text-lg font-black text-white font-mono">₹{{ number_format((float)$payment->amount, 2) }}</span>
                        </div>

                        <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block mb-1">Payment Mode</span>
                            <span class="font-bold text-slate-200 flex items-center gap-1.5 mt-0.5">
                                <span>{{ $payment->mode->icon() }}</span>
                                <span>{{ $payment->mode->label() }}</span>
                            </span>
                        </div>

                        <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
                            <span class="text-slate-400 block mb-1">Payment Purpose</span>
                            <span class="font-bold text-cyan-300 mt-0.5 block">{{ $payment->purpose->label() }}</span>
                        </div>
                    </div>

                    <!-- Mode Specific Identifiers -->
                    <div class="p-4 bg-slate-900 rounded-xl border border-slate-800 text-xs space-y-2">
                        @if($payment->utr_number)
                            <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                                <span class="text-slate-400 font-semibold">UPI UTR Number:</span>
                                <span class="font-mono font-bold text-cyan-300 text-sm">{{ $payment->utr_number }}</span>
                            </div>
                        @endif

                        @if($payment->bank_reference)
                            <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                                <span class="text-slate-400 font-semibold">Bank Transfer Reference:</span>
                                <span class="font-mono font-bold text-purple-300 text-sm">{{ $payment->bank_reference }}</span>
                            </div>
                        @endif

                        @if($payment->gateway_order_id)
                            <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                                <span class="text-slate-400 font-semibold">Gateway Order ID:</span>
                                <span class="font-mono text-slate-200">{{ $payment->gateway_order_id }}</span>
                            </div>
                        @endif

                        @if($payment->gateway_payment_id)
                            <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                                <span class="text-slate-400 font-semibold">Gateway Payment ID:</span>
                                <span class="font-mono text-emerald-300 font-bold">{{ $payment->gateway_payment_id }}</span>
                            </div>
                        @endif

                        @if($payment->verified_by)
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-slate-400 font-semibold">Verified By:</span>
                                <span class="font-semibold text-slate-200">
                                    {{ $payment->verifiedBy->name ?? 'Admin #' . $payment->verified_by }} ({{ $payment->verified_at?->format('d M Y, h:i A') }})
                                </span>
                            </div>
                        @endif

                        @if($payment->rejection_reason)
                            <div class="p-3 mt-2 rounded-lg bg-rose-950/60 border border-rose-500/30 text-rose-300">
                                <span class="font-bold block mb-0.5">Rejection Reason:</span>
                                {{ $payment->rejection_reason }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Proof Screenshot Card -->
                @if($payment->screenshot_url)
                    <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-3">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                            <span>📷</span> Uploaded Proof Screenshot
                        </h2>
                        <div class="bg-slate-900 p-2 rounded-xl border border-slate-800 flex items-center justify-center max-h-[500px] overflow-hidden">
                            <a href="{{ $payment->screenshot_url }}" target="_blank" title="Open full size">
                                <img src="{{ $payment->screenshot_url }}" alt="Proof" class="max-h-[480px] object-contain rounded-lg shadow hover:opacity-95 transition">
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Audit Log Trail Card -->
                <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>📜</span> Audit Action History
                    </h2>

                    <div class="space-y-3">
                        @forelse($payment->auditLogs as $log)
                            <div class="p-3.5 bg-slate-900 rounded-xl border border-slate-800 text-xs flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        @if($log->action->value === 'approved')
                                            <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold text-[10px] uppercase">Approved</span>
                                        @elseif($log->action->value === 'rejected')
                                            <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold text-[10px] uppercase">Rejected</span>
                                        @elseif($log->action->value === 'refunded')
                                            <span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold text-[10px] uppercase">Refunded</span>
                                        @endif
                                        <span class="text-slate-300 font-semibold">by {{ $log->admin->name ?? 'Admin' }}</span>
                                    </div>
                                    @if($log->notes)
                                        <p class="text-slate-400 italic">"{{ $log->notes }}"</p>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 italic">No admin actions recorded yet for this transaction.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right Col: Billing Agent & Actions -->
            <div class="space-y-6">
                <!-- Agent Identity Card -->
                <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400">Billing Agent Profile</h2>

                    @if($payment->user)
                        <div class="space-y-3 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Billing Agent Name</span>
                                <span class="text-sm font-bold text-white">{{ $payment->user->name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Email Address</span>
                                <span class="text-slate-200 font-mono">{{ $payment->user->email }}</span>
                            </div>
                            @if($payment->user->phone)
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Phone Number</span>
                                    <span class="text-cyan-300 font-mono">{{ $payment->user->phone }}</span>
                                </div>
                            @endif
                            <div>
                                <span class="text-slate-400 block text-[11px]">Account Status</span>
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-200 font-semibold text-[10px] uppercase">
                                    {{ $payment->user->status ?? 'Active' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-slate-500">Billing agent record not found.</p>
                    @endif
                </div>

                <!-- Admin Action Card -->
                @if($payment->status->value === 'pending_verification')
                    <div class="bg-slate-950 p-6 rounded-2xl border border-amber-500/30 space-y-4">
                        <h2 class="text-sm font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                            <span>⚡</span> Quick Actions
                        </h2>

                        <!-- Approve Form -->
                        <form action="{{ route('admin.payments.approve', $payment->id) }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="text" name="notes" placeholder="Approval notes (optional)" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-emerald-500">
                            <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1 shadow-md shadow-emerald-600/30">
                                <span>✅</span> Approve Payment
                            </button>
                        </form>

                        <div class="border-t border-slate-800/80 pt-3">
                            <!-- Reject Form -->
                            <form action="{{ route('admin.payments.reject', $payment->id) }}" method="POST" class="space-y-3">
                                @csrf
                                <textarea name="rejection_reason" rows="2" required placeholder="Mandatory rejection reason..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-rose-500"></textarea>
                                <button type="submit" class="w-full py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1 shadow-md shadow-rose-600/30">
                                    <span>❌</span> Reject Payment
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
