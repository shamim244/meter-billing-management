<x-admin-layout title="Payments & Verification Queue">
    <div x-data="{
        approveModal: false,
        rejectModal: false,
        refundModal: false,
        screenshotModal: false,
        currentPayment: null,
        rejectionReason: '',
        notes: '',
        refundReason: '',
        currentScreenshot: '',
        openApprove(p) {
            this.currentPayment = p;
            this.notes = '';
            this.approveModal = true;
        },
        openReject(p) {
            this.currentPayment = p;
            this.rejectionReason = '';
            this.notes = '';
            this.rejectModal = true;
        },
        openRefund(p) {
            this.currentPayment = p;
            this.refundReason = '';
            this.refundModal = true;
        },
        openScreenshot(url) {
            this.currentScreenshot = url;
            this.screenshotModal = true;
        }
    }" class="space-y-6">

        <!-- Top Navigation Tabs -->
        @include('admin.payments.nav')

        <!-- Top Header & Actions -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2">
                    <span>💳</span> Payment Gateway & Verification Queue
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Manage online PG transactions, manual UPI & bank transfer verification queues.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.payments.settings') }}" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold border border-slate-700 transition flex items-center gap-2 shadow-sm">
                    <span>⚙️</span> Payment Settings & Gateways
                </a>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-slate-950 p-5 rounded-2xl border border-amber-500/30 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Pending Verification</span>
                    <span class="text-lg">⏳</span>
                </div>
                <div class="text-2xl font-black text-amber-400 mt-2">{{ number_format($pendingCount) }}</div>
                <span class="text-[11px] text-slate-400">Manual UPI & Bank Transfers awaiting review</span>
            </div>

            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Collected</span>
                    <span class="text-lg">💰</span>
                </div>
                <div class="text-2xl font-black text-emerald-400 mt-2">₹{{ number_format($totalCollected, 2) }}</div>
                <span class="text-[11px] text-slate-400">Across all successful payments</span>
            </div>

            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Successful Payments</span>
                    <span class="text-lg">✅</span>
                </div>
                <div class="text-2xl font-black text-white mt-2">{{ number_format($successCount) }}</div>
                <span class="text-[11px] text-slate-400">PG + Approved manual payments</span>
            </div>

            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rejected Payments</span>
                    <span class="text-lg">❌</span>
                </div>
                <div class="text-2xl font-black text-rose-400 mt-2">{{ number_format($rejectedCount) }}</div>
                <span class="text-[11px] text-slate-400">Invalid UTRs / mismatched transfers</span>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Queue Tabs -->
            <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-900 rounded-xl border border-slate-800">
                <a href="{{ route('admin.payments.index', ['status' => 'pending_verification', 'mode' => $modeFilter, 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $statusFilter === 'pending_verification' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 shadow-sm' : 'text-slate-400 hover:text-white' }}">
                    <span>⏳ Pending Queue</span>
                    @if($pendingCount > 0)
                        <span class="px-1.5 py-0.5 rounded-md bg-amber-500 text-slate-950 text-[10px] font-extrabold">{{ $pendingCount }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.payments.index', ['status' => 'all', 'mode' => $modeFilter, 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white' }}">
                    All Transactions
                </a>
                <a href="{{ route('admin.payments.index', ['status' => 'success', 'mode' => $modeFilter, 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'success' ? 'bg-emerald-600/20 text-emerald-300 border border-emerald-500/40 shadow-sm' : 'text-slate-400 hover:text-white' }}">
                    Successful
                </a>
                <a href="{{ route('admin.payments.index', ['status' => 'rejected', 'mode' => $modeFilter, 'search' => $search]) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === 'rejected' ? 'bg-rose-600/20 text-rose-300 border border-rose-500/40 shadow-sm' : 'text-slate-400 hover:text-white' }}">
                    Rejected
                </a>
            </div>

            <!-- Mode & Search Filters -->
            <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="status" value="{{ $statusFilter }}">

                <select name="mode" onchange="this.form.submit()" class="text-xs font-semibold bg-slate-900 border-slate-800 rounded-xl text-slate-200 py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Modes</option>
                    <option value="pg" {{ $modeFilter === 'pg' ? 'selected' : '' }}>⚡ PG (Online)</option>
                    <option value="manual_upi" {{ $modeFilter === 'manual_upi' ? 'selected' : '' }}>📱 Manual UPI</option>
                    <option value="bank_transfer" {{ $modeFilter === 'bank_transfer' ? 'selected' : '' }}>🏦 Bank Transfer</option>
                </select>

                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search UTR, Ref, Agent..." class="text-xs bg-slate-900 border-slate-800 rounded-xl text-slate-200 py-2 pl-8 pr-4 focus:ring-indigo-500 focus:border-indigo-500 w-48 sm:w-64 placeholder-slate-500">
                    <svg class="w-3.5 h-3.5 text-slate-500 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <button type="submit" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/90 text-[11px] uppercase font-bold text-slate-400 border-b border-slate-800 tracking-wider">
                        <tr>
                            <th class="px-4 py-3.5">ID & Date</th>
                            <th class="px-4 py-3.5">Billing Agent / User</th>
                            <th class="px-4 py-3.5">Mode & Purpose</th>
                            <th class="px-4 py-3.5">Amount</th>
                            <th class="px-4 py-3.5">Reference / Proof</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($payments as $payment)
                            <tr class="hover:bg-slate-900/50 transition">
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="font-mono font-bold text-white">#{{ $payment->id }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $payment->created_at->format('d M Y, h:i A') }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-white">{{ $payment->user->name ?? 'Unknown Agent' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $payment->user->email ?? '-' }}</div>
                                    @if($payment->user?->phone)
                                        <div class="text-[10px] text-cyan-400/80 font-mono">{{ $payment->user->phone }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 font-semibold text-slate-200">
                                        <span>{{ $payment->mode->icon() }}</span>
                                        <span>{{ $payment->mode->label() }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-1">
                                        {{ $payment->purpose->label() }}
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="text-sm font-black text-white font-mono">
                                        ₹{{ number_format((float)$payment->amount, 2) }}
                                    </div>
                                    <span class="text-[10px] text-slate-400 uppercase font-semibold">{{ $payment->currency }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($payment->utr_number)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] uppercase font-bold text-slate-400">UTR:</span>
                                            <span class="font-mono font-bold text-cyan-300">{{ $payment->utr_number }}</span>
                                        </div>
                                    @endif
                                    @if($payment->bank_reference)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] uppercase font-bold text-slate-400">Ref:</span>
                                            <span class="font-mono font-bold text-purple-300">{{ $payment->bank_reference }}</span>
                                        </div>
                                    @endif
                                    @if($payment->gateway_payment_id)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] uppercase font-bold text-slate-400">PG ID:</span>
                                            <span class="font-mono text-[11px] text-emerald-300">{{ $payment->gateway_payment_id }}</span>
                                        </div>
                                    @endif
                                    @if($payment->screenshot_url)
                                        <button @click="openScreenshot('{{ $payment->screenshot_url }}')" class="mt-1 text-[11px] text-blue-400 hover:text-blue-300 underline font-semibold flex items-center gap-1">
                                            <span>📷</span> View Screenshot
                                        </button>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($payment->status->value === 'pending_verification')
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 inline-flex items-center gap-1 animate-pulse">
                                            <span>⏳</span> Pending Review
                                        </span>
                                    @elseif($payment->status->value === 'success')
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 inline-flex items-center gap-1">
                                            <span>✅</span> Successful
                                        </span>
                                    @elseif($payment->status->value === 'rejected')
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40 inline-flex items-center gap-1" title="{{ $payment->rejection_reason }}">
                                            <span>❌</span> Rejected
                                        </span>
                                    @elseif($payment->status->value === 'failed')
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40 inline-flex items-center gap-1">
                                            <span>⚠️</span> Failed
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700 inline-flex items-center gap-1">
                                            <span>⏱️</span> Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($payment->status->value === 'pending_verification')
                                            <button @click="openApprove({{ json_encode($payment) }})" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                                                <span>✅</span> Approve
                                            </button>
                                            <button @click="openReject({{ json_encode($payment) }})" class="px-2.5 py-1.5 bg-rose-600/80 hover:bg-rose-600 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm">
                                                <span>❌</span> Reject
                                            </button>
                                        @elseif($payment->status->value === 'success')
                                            <button @click="openRefund({{ json_encode($payment) }})" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-semibold transition flex items-center gap-1" title="Log manual refund">
                                                <span>🔄</span> Refund
                                            </button>
                                        @endif

                                        <a href="{{ route('admin.payments.show', $payment->id) }}" class="p-1.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition" title="Inspect Full Payment Trail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    <div class="text-3xl mb-2">💳</div>
                                    <p class="font-bold text-sm text-slate-400">No payments found</p>
                                    <p class="text-xs text-slate-500 mt-0.5">There are no payment transactions matching this filter criteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($payments->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>

        <!-- Approve Modal -->
        <div x-show="approveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="approveModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-bold">
                        ✅
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Approve Payment</h3>
                        <p class="text-xs text-slate-400">Confirm receipt of funds in bank/UPI account</p>
                    </div>
                </div>

                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 text-xs space-y-1.5">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Amount:</span>
                        <span class="font-bold text-white font-mono" x-text="'₹' + (currentPayment?.amount ?? 0)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Billing Agent:</span>
                        <span class="font-semibold text-slate-200" x-text="currentPayment?.user?.name ?? currentPayment?.tenant?.name ?? 'Agent'"></span>
                    </div>
                    <div class="flex justify-between" x-show="currentPayment?.utr_number">
                        <span class="text-slate-400">UTR:</span>
                        <span class="font-mono text-cyan-300 font-bold" x-text="currentPayment?.utr_number"></span>
                    </div>
                    <div class="flex justify-between" x-show="currentPayment?.bank_reference">
                        <span class="text-slate-400">Bank Ref:</span>
                        <span class="font-mono text-purple-300 font-bold" x-text="currentPayment?.bank_reference"></span>
                    </div>
                </div>

                <form :action="'/admin/payments/' + currentPayment?.id + '/approve'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Audit Notes (Optional)</label>
                        <input type="text" name="notes" x-model="notes" placeholder="Verified in SBI statement at 10:45 AM" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="approveModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-md shadow-emerald-600/30">
                            <span>✅</span> Confirm Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject Modal (Mandatory Reason Required) -->
        <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="rejectModal = false" class="bg-slate-900 border border-rose-500/30 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-xl font-bold">
                        ❌
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Reject Payment</h3>
                        <p class="text-xs text-rose-400">A mandatory rejection reason is required</p>
                    </div>
                </div>

                <form :action="'/admin/payments/' + currentPayment?.id + '/reject'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Rejection Reason <span class="text-rose-400">*</span></label>
                        <textarea name="rejection_reason" x-model="rejectionReason" rows="3" required placeholder="e.g. UTR number not found in bank statement, amount mismatch (claimed ₹1000, received ₹500)" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-rose-500 focus:border-rose-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Internal Notes (Optional)</label>
                        <input type="text" name="notes" x-model="notes" placeholder="Contacted billing agent via phone on 21-Aug" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-rose-500 focus:border-rose-500">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="rejectModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                            Cancel
                        </button>
                        <button type="submit" :disabled="!rejectionReason || rejectionReason.trim().length < 3" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-md shadow-rose-600/30">
                            <span>❌</span> Reject Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Refund Modal -->
        <div x-show="refundModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="refundModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-800 text-slate-200 flex items-center justify-center text-xl font-bold">
                        🔄
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Log Manual Refund</h3>
                        <p class="text-xs text-slate-400">Record refund action in audit log</p>
                    </div>
                </div>

                <form :action="'/admin/payments/' + currentPayment?.id + '/refund'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Refund Reason & Reference <span class="text-rose-400">*</span></label>
                        <textarea name="refund_reason" x-model="refundReason" rows="3" required placeholder="e.g. Refunded ₹500 via UPI back to agent UPI ID on 21-Aug (Ref: 9912837)" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="refundModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                            Cancel
                        </button>
                        <button type="submit" :disabled="!refundReason || refundReason.trim().length < 3" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-xs font-bold transition">
                            Log Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Screenshot Modal -->
        <div x-show="screenshotModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md">
            <div @click.away="screenshotModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full p-4 shadow-2xl space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-300">Payment Screenshot Proof</span>
                    <button @click="screenshotModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>
                <div class="max-h-[75vh] overflow-auto flex items-center justify-center bg-slate-950 rounded-xl p-2">
                    <img :src="currentScreenshot" alt="Proof" class="max-h-[70vh] object-contain rounded-lg shadow">
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
