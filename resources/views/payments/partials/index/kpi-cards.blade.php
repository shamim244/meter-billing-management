<!-- Summary KPI Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Paid</span>
            <span class="text-lg">💰</span>
        </div>
        <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">₹{{ number_format($stats['total_paid'], 2) }}</div>
        <span class="text-[11px] text-slate-400">Successful payments to date</span>
    </div>

    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Verification</span>
            <span class="text-lg">⏳</span>
        </div>
        <div class="text-2xl font-black text-amber-500 mt-1">{{ $stats['pending_verification'] }}</div>
        <span class="text-[11px] text-slate-400">Awaiting admin review</span>
    </div>

    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Last Transaction</span>
            <span class="text-lg">⚡</span>
        </div>
        <div class="text-sm font-bold text-slate-900 dark:text-white mt-2">
            @if($stats['recent_success'])
                ₹{{ number_format((float)$stats['recent_success']->amount, 2) }} <span class="text-slate-400 font-normal text-xs">on {{ $stats['recent_success']->created_at->format('d M Y') }}</span>
            @else
                <span class="text-slate-400">No transactions yet</span>
            @endif
        </div>
        <span class="text-[11px] text-slate-400">Most recent payment</span>
    </div>
</div>
