<!-- 3-Column Meta Banner: [Tariff Category] | [Total Amount] | [Billing Basis] -->
<div class="px-4 sm:px-5 py-2.5 bg-slate-100 dark:bg-slate-800/90 border-b border-slate-200/80 dark:border-slate-700 flex items-center justify-between gap-2">
    <!-- Left: Tariff Category -->
    <div class="flex items-center gap-1 min-w-[65px] sm:min-w-[75px]">
        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden sm:inline">Tariff:</span>
        <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-xs font-black font-mono bg-indigo-100 dark:bg-indigo-950/90 text-indigo-700 dark:text-indigo-300 border border-indigo-300/80 dark:border-indigo-800" x-text="bill.tariff_category || 'GEN'"></span>
    </div>

    <!-- Center: Total Amount -->
    <div class="text-center">
        <div class="flex items-center justify-center gap-1 leading-none mb-0.5">
            <template x-if="Number(bill.total_amount) < 0">
                <span class="px-1.5 py-0.2 text-[8px] font-bold uppercase tracking-wider rounded bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">Advance / Credit</span>
            </template>
            <template x-if="Number(bill.total_amount) >= 0">
                <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider block" 
                      :class="colorSettings?.enabled && Number(bill.total_amount) >= (colorSettings.amount_danger_floor ?? 2500) ? 'text-rose-600 font-black' : 'text-slate-400 dark:text-slate-500'">Total Amount</span>
            </template>
            <template x-if="colorSettings?.enabled && Number(bill.total_amount) >= (colorSettings.amount_danger_floor ?? 2500)">
                <span class="px-1 py-0.2 text-[8px] font-black uppercase tracking-wider rounded bg-rose-500/10 text-rose-600 border border-rose-500/20 animate-pulse">Alert</span>
            </template>
        </div>
        <div class="leading-tight" 
             :class="[
                 amountSize === 'standard' ? 'text-lg sm:text-xl' : 'text-xl sm:text-2xl',
                 getAmountStyle(bill.total_amount)
             ]" 
             x-text="formatCurrency(bill.total_amount)"></div>
    </div>

    <!-- Right: Billing Basis -->
    <div class="flex items-center justify-end gap-1.5 flex-wrap">
        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden sm:inline">Basis:</span>
        <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-xs font-black font-mono" :class="bill.billing_basis === 'OK' ? 'bg-emerald-100 dark:bg-emerald-950/90 text-emerald-700 dark:text-emerald-300 border border-emerald-300/80 dark:border-emerald-800' : (bill.billing_basis === 'MD' ? 'bg-amber-100 dark:bg-amber-950/90 text-amber-700 dark:text-amber-300 border border-amber-300/80 dark:border-amber-800' : 'bg-rose-100 dark:bg-rose-950/90 text-rose-700 dark:text-rose-300 border border-rose-300/80 dark:border-rose-800')" :title="'Billing Basis: ' + bill.billing_basis" x-text="bill.billing_basis || 'OK'"></span>
        <template x-if="bill.is_consecutive_alert">
            <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-xs font-black uppercase bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800"
                  :title="bill.consecutive_count + ' Consecutive Estimated Cycles (' + (bill.billing_basis || 'LK') + ')'"
                  x-text="'⚠️ ' + bill.consecutive_count + 'x ' + (bill.billing_basis || 'LK')">
            </span>
        </template>
    </div>
</div>
