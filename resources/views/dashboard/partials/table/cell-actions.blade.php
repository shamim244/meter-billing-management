<!-- Amount -->
<td class="py-3 px-3 text-right" :class="getAmountStyle(bill.total_amount)">
    <div class="flex items-center justify-end gap-1.5">
        <template x-if="Number(bill.total_amount) < 0">
            <span class="px-1.5 py-0.2 rounded text-[8px] font-bold uppercase bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">Advance / Credit</span>
        </template>
        <template x-if="colorSettings?.enabled && Number(bill.total_amount) >= (colorSettings.amount_danger_floor ?? 2500)">
            <span class="px-1.5 py-0.2 rounded text-[8px] font-black uppercase bg-rose-500/10 text-rose-600 border border-rose-500/20 animate-pulse">Alert</span>
        </template>
        <span x-text="formatCurrency(bill.total_amount)"></span>
    </div>
</td>

<!-- Month -->
<td class="py-3 px-3 text-center font-mono text-slate-600 dark:text-slate-300 font-bold text-xs" x-text="bill.bill_month_label || (bill.billing_month + '/' + bill.billing_year)"></td>

<!-- Status Actions (Instant Update) -->
<td class="py-3 px-3 text-center">
    <div class="inline-flex items-center gap-1">
        <button @click="updateBillStatus(bill, 'submitted')" :class="bill.review_status === 'submitted' ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 hover:text-emerald-700'" class="px-2.5 py-1 rounded-lg text-xs transition active:scale-95" title="Mark Submitted">
            ✅
        </button>
        <button @click="updateBillStatus(bill, 'critical')" :class="bill.review_status === 'critical' ? 'bg-rose-600 text-white shadow-md ring-2 ring-rose-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-rose-100 dark:hover:bg-rose-900/50 hover:text-rose-700'" class="px-2.5 py-1 rounded-lg text-xs transition active:scale-95" title="Mark Critical">
            ❌
        </button>
        <button @click="updateBillStatus(bill, 'doubt')" :class="bill.review_status === 'doubt' ? 'bg-amber-600 text-white shadow-md ring-2 ring-amber-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-amber-100 dark:hover:bg-amber-900/50 hover:text-amber-700'" class="px-2.5 py-1 rounded-lg text-xs transition active:scale-95" title="Mark Doubt">
            ⚠️
        </button>
    </div>
</td>

<!-- Tag -->
<td class="py-3 px-3 text-center">
    <select @change="setBillTag(bill, $event.target.value)" 
            class="text-[10px] font-bold rounded-lg border-slate-200 dark:border-slate-700 py-1 px-1.5 bg-slate-50 dark:bg-slate-800 cursor-pointer"
            :class="getTagBadgeClass(bill.tag || defaultTag)">
        <template x-for="t in availableTags" :key="t.code">
            <option :value="t.code" :selected="(bill.tag === t.code || (!bill.tag && t.code === defaultTag))" x-text="t.short_label || t.label"></option>
        </template>
    </select>
</td>

<!-- Remark -->
<td class="py-3 px-3 max-w-[170px]">
    <div class="flex items-center gap-1.5">
        <input type="text" 
               x-model="bill.remark" 
               @focus="onRemarkFocus(bill)" 
               @blur="onRemarkBlur(bill)" 
               placeholder="Add note..." 
               class="w-full text-[11px] rounded-lg border-slate-200 dark:border-slate-700 px-2 py-1 focus:ring-blue-500 focus:border-blue-500 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white" />
    </div>
</td>

<!-- Actions & PDF Download -->
<td class="py-3 px-3 text-center">
    <div class="inline-flex items-center justify-center gap-1.5">
        <template x-if="bill.has_pdf">
            <div class="inline-flex items-center gap-1">
                <button @click="openPdfModal(bill)" class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 dark:text-cyan-400 hover:text-blue-800 dark:hover:text-cyan-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 px-2 py-1 rounded-lg transition" title="Preview PDF Bill">
                    📄 View
                </button>
                <button @click="downloadSingleBill(bill)" :disabled="syncingSingle === bill.ca_number" class="p-1 text-slate-400 hover:text-blue-600 dark:hover:text-cyan-300 transition" title="Re-download / Sync this Bill">
                    <span x-show="syncingSingle !== bill.ca_number">⚡</span>
                    <svg x-show="syncingSingle === bill.ca_number" class="w-3.5 h-3.5 animate-spin text-blue-600 dark:text-cyan-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </button>
            </div>
        </template>
        <template x-if="!bill.has_pdf">
            <button @click="downloadSingleBill(bill)" :disabled="syncingSingle === bill.ca_number" class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-cyan-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 px-2.5 py-1 rounded-lg transition" title="Download Official PDF">
                <span x-show="syncingSingle !== bill.ca_number">⚡ Pull</span>
                <span x-show="syncingSingle === bill.ca_number" class="flex items-center gap-1">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Pulling
                </span>
            </button>
        </template>
    </div>
</td>
