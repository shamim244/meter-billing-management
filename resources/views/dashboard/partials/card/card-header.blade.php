<!-- Top Header Bar (Mobile Responsive & Crisp) -->
<div class="px-4 sm:px-5 py-3.5 sm:py-4 relative text-white" :class="{
    'bg-gradient-to-r from-emerald-900 to-slate-900': bill.review_status === 'submitted',
    'bg-gradient-to-r from-rose-900 to-slate-900': bill.review_status === 'critical',
    'bg-gradient-to-r from-amber-900 to-slate-900': bill.review_status === 'doubt',
    'bg-gradient-to-r from-slate-950 to-slate-900': bill.review_status === 'pending'
}">
    <div class="flex items-start justify-between gap-2.5">
        <!-- Left: 2-Digit Avatar + Name + CA + Copy + Badges -->
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
            <div class="w-9 h-9 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 text-cyan-300 font-black text-sm flex items-center justify-center font-mono shrink-0 select-none" x-text="bill.ca_number.slice(-2)"></div>
            <div class="min-w-0 flex-1">
                <h2 class="text-sm sm:text-base font-bold text-white tracking-tight truncate select-text" x-text="bill.consumer_name || 'CONSUMER ACCOUNT'"></h2>
                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                    @include('dashboard.partials.card.header.ca-title')
                    @include('dashboard.partials.card.header.badges-strip')
                </div>
            </div>
        </div>

        <!-- Right: Month & Status Badge -->
        @include('dashboard.partials.card.header.status-month')
    </div>
</div>
