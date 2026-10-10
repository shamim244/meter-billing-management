<!-- Right: Month & Status Badge -->
<div class="text-right space-y-1 shrink-0 flex flex-col items-end">
    <div class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-white/10 text-cyan-300 font-mono inline-block whitespace-nowrap" x-text="bill.bill_month_label || 'MONTH BILL'"></div>
    <div>
        <span :class="{
            'bg-emerald-500 text-white': bill.review_status === 'submitted',
            'bg-rose-500 text-white': bill.review_status === 'critical',
            'bg-amber-500 text-white': bill.review_status === 'doubt',
            'bg-slate-700 text-slate-300': bill.review_status === 'pending'
        }" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider inline-block whitespace-nowrap" x-text="bill.review_status === 'pending' ? '⏳ PENDING' : (bill.review_status === 'submitted' ? '✅ SUBMITTED' : (bill.review_status === 'critical' ? '❌ CRITICAL' : '⚠️ DOUBT'))"></span>
        <template x-if="isCaPendingSync(bill.ca_number)">
            <div class="mt-1">
                <span class="px-2 py-0.5 rounded-md text-[9px] font-bold bg-amber-400 text-slate-950 shadow-2xs inline-flex items-center gap-1 animate-pulse" title="Saved locally on device, waiting to sync with server">
                    <span>☁️ Offline Saved</span>
                </span>
            </div>
        </template>
        <template x-if="bill._syncError">
            <div class="mt-1">
                <span class="px-2 py-0.5 rounded-md text-[9px] font-bold bg-rose-600 text-white shadow-2xs inline-flex items-center gap-1 animate-pulse" :title="bill._syncErrorMsg || 'Server rejected update'">
                    <span>⚠️ Sync Failed (Reverted)</span>
                </span>
            </div>
        </template>
    </div>
</div>
