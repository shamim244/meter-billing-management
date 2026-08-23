<x-admin-layout>
    <x-slot name="header">
        Manual Payment Verification Queue
    </x-slot>

    <div x-data="{
        approveModal: false,
        rejectModal: false,
        previewModal: false,
        previewImageUrl: null,
        currentPayment: null,
        rejectionReason: '',
        notes: '',
        openApprove(payment) {
            this.currentPayment = payment;
            this.notes = '';
            this.approveModal = true;
        },
        openReject(payment) {
            this.currentPayment = payment;
            this.rejectionReason = '';
            this.notes = '';
            this.rejectModal = true;
        },
        openPreview(imageUrl) {
            this.previewImageUrl = imageUrl;
            this.previewModal = true;
        }
    }" class="space-y-6">

        <!-- Top Navigation Tabs -->
        @include('admin.payments.nav')

        <!-- Flash Alerts -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold flex items-center justify-between shadow-lg">
                <span>✅ {{ session('success') }}</span>
                <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold flex items-center justify-between shadow-lg">
                <span>❌ {{ session('error') }}</span>
                <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
            </div>
        @endif

        <!-- Queue Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Manual Queue</span>
                <div class="text-2xl font-black text-amber-400 font-mono">{{ $pendingPayments->total() }}</div>
                <span class="text-[11px] text-slate-500">Requires bank statement verification</span>
            </div>

            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Volume Value</span>
                <div class="text-2xl font-black text-cyan-400 font-mono">₹{{ number_format($totalPendingAmount, 2) }}</div>
                <span class="text-[11px] text-slate-500">Total claimed unverified balance</span>
            </div>

            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Channel Distribution</span>
                <div class="flex items-center gap-3 text-xs font-bold pt-1">
                    <span class="text-purple-400">📱 UPI: {{ $upiPendingCount }}</span>
                    <span class="text-slate-600">•</span>
                    <span class="text-blue-400">🏦 Bank: {{ $bankPendingCount }}</span>
                </div>
                <span class="text-[11px] text-slate-500">Breakdown by payment mode</span>
            </div>
        </div>

        <!-- Filter and Search Bar -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.payments.manual') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ empty($modeFilter) ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
                    All Pending ({{ $pendingPayments->total() }})
                </a>
                <a href="{{ route('admin.payments.manual', ['mode' => 'manual_upi']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $modeFilter === 'manual_upi' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
                    📱 UPI Only ({{ $upiPendingCount }})
                </a>
                <a href="{{ route('admin.payments.manual', ['mode' => 'bank_transfer']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $modeFilter === 'bank_transfer' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
                    🏦 Bank NEFT/IMPS ({{ $bankPendingCount }})
                </a>
            </div>

            <form method="GET" action="{{ route('admin.payments.manual') }}" class="flex items-center gap-2 w-full sm:w-auto">
                <input type="hidden" name="mode" value="{{ $modeFilter }}">
                <div class="relative w-full sm:w-64">
                    <span class="absolute left-3 top-2.5 text-slate-500 text-xs">🔍</span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search UTR, Ref, Agent..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl pl-8 pr-3 py-2 text-slate-200 placeholder-slate-500 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- Verification Table -->
        <div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800 font-bold">
                        <tr>
                            <th class="py-3 px-4">Payment ID & Time</th>
                            <th class="py-3 px-4">Billing Agent</th>
                            <th class="py-3 px-4">Mode & Transaction Reference</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Receipt Proof</th>
                            <th class="py-3 px-4 text-right">Verification Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($pendingPayments as $p)
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-white">#{{ $p->id }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $p->created_at->format('d M Y, h:i A') }}</div>
                                    <span class="text-[9px] text-slate-500">({{ $p->created_at->diffForHumans() }})</span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-white">{{ $p->user->name ?? 'Agent #' . $p->user_id }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $p->user->email ?? 'No email' }}</div>
                                    @if(!empty($p->user->phone))
                                        <div class="text-[10px] text-slate-500 font-mono">{{ $p->user->phone }}</div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        @if($p->mode->value === 'manual_upi')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20">📱 UPI QR</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">🏦 Bank Transfer</span>
                                        @endif
                                    </div>
                                    <div class="font-mono text-slate-200 text-xs font-bold flex items-center gap-1">
                                        <span>Ref:</span>
                                        <span class="text-cyan-300">{{ $p->utr_number ?: ($p->bank_reference ?: '—') }}</span>
                                    </div>
                                    <span class="text-[10px] text-slate-400">Purpose: {{ $p->purpose->label() }}</span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="text-base font-black text-white font-mono">₹{{ number_format((float)$p->amount, 2) }}</span>
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($p->screenshot_path)
                                        <button type="button" @click="openPreview('{{ Storage::url($p->screenshot_path) }}')" class="flex items-center gap-1.5 p-1.5 rounded-xl bg-slate-900 border border-slate-800 hover:border-indigo-500/50 transition group">
                                            <img src="{{ Storage::url($p->screenshot_path) }}" alt="Receipt" class="w-10 h-10 object-cover rounded-lg">
                                            <span class="text-[11px] text-indigo-400 group-hover:text-indigo-300 font-bold pr-1">View Slip</span>
                                        </button>
                                    @else
                                        <span class="text-slate-500 text-[11px] italic">No image uploaded</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-right space-x-1.5">
                                    <button type="button" @click="openApprove({{ json_encode($p) }})" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-sm shadow-emerald-600/30">
                                        ✓ Approve
                                    </button>
                                    <button type="button" @click="openReject({{ json_encode($p) }})" class="px-3 py-1.5 bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 rounded-xl text-xs font-bold transition">
                                        ✕ Reject
                                    </button>
                                    <a href="{{ route('admin.payments.show', $p->id) }}" class="px-2 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                                        Details →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <div class="text-3xl mb-2">🎉</div>
                                    <div class="text-sm font-bold text-slate-300">All Manual Payments Verified!</div>
                                    <div class="text-xs text-slate-500 mt-1">No pending UPI or Bank Transfer records in queue.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($pendingPayments->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $pendingPayments->links() }}
                </div>
            @endif
        </div>

        <!-- Approve Modal -->
        <div x-show="approveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="approveModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-bold">
                        ✓
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Approve Payment #<span x-text="currentPayment?.id"></span></h3>
                        <p class="text-xs text-slate-400">Credit ₹<span x-text="currentPayment?.amount"></span> to Billing Agent</p>
                    </div>
                </div>

                <form :action="'/admin/payments/' + currentPayment?.id + '/approve'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Verification Notes (Optional)</label>
                        <input type="text" name="notes" x-model="notes" placeholder="e.g. Verified against SBI Statement Ref 192837" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="approveModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-md shadow-emerald-600/30">
                            <span>✓</span> Confirm Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject Modal -->
        <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="rejectModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-xl font-bold">
                        ✕
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Reject Payment #<span x-text="currentPayment?.id"></span></h3>
                        <p class="text-xs text-rose-400">A mandatory rejection reason is required</p>
                    </div>
                </div>

                <form :action="'/admin/payments/' + currentPayment?.id + '/reject'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Rejection Reason <span class="text-rose-400">*</span></label>
                        <textarea name="rejection_reason" x-model="rejectionReason" rows="3" required placeholder="e.g. UTR number not found in bank statement records, amount mismatch" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-rose-500 focus:border-rose-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Internal Notes (Optional)</label>
                        <input type="text" name="notes" x-model="notes" placeholder="Contacted agent via phone on 21-Aug" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-slate-200 p-2.5 focus:ring-rose-500 focus:border-rose-500">
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

        <!-- Receipt Zoom Preview Modal -->
        <div x-show="previewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md">
            <div @click.away="previewModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full p-4 shadow-2xl space-y-3">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <h3 class="text-sm font-bold text-white">Payment Receipt Proof</h3>
                    <button type="button" @click="previewModal = false" class="p-1 text-slate-400 hover:text-white text-base">✕</button>
                </div>
                <div class="flex items-center justify-center max-h-[70vh] overflow-auto bg-slate-950 rounded-xl p-2">
                    <img :src="previewImageUrl" alt="Receipt Full" class="max-h-[65vh] object-contain rounded-lg">
                </div>
                <div class="flex justify-end">
                    <a :href="previewImageUrl" target="_blank" download class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition">
                        Open in Full Tab ↗
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
