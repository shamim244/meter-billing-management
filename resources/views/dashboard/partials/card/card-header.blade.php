<!-- Top Header Bar (Mobile Responsive & Crisp) -->
<div class="px-4 sm:px-5 py-3.5 sm:py-4 relative text-white" :class="{
    'bg-gradient-to-r from-emerald-900 to-slate-900': bill.review_status === 'submitted',
    'bg-gradient-to-r from-rose-900 to-slate-900': bill.review_status === 'critical',
    'bg-gradient-to-r from-amber-900 to-slate-900': bill.review_status === 'doubt',
    'bg-gradient-to-r from-slate-950 to-slate-900': bill.review_status === 'pending'
}">
    <div class="flex items-start justify-between gap-2.5">
        <!-- Left: 2-Digit Avatar + Name + CA + Copy -->
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
            <div class="w-9 h-9 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 text-cyan-300 font-black text-sm flex items-center justify-center font-mono shrink-0 select-none" x-text="bill.ca_number.slice(-2)"></div>
            <div class="min-w-0 flex-1">
                <h2 class="text-sm sm:text-base font-bold text-white tracking-tight truncate select-text" x-text="bill.consumer_name || 'CONSUMER ACCOUNT'"></h2>
                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                    <span class="font-mono text-cyan-200 text-xs sm:text-sm font-semibold select-text select-all cursor-pointer hover:text-white transition py-0.5" 
                          @click="copyText(bill.ca_number, bill.id)" 
                          title="Tap to copy or long-press to select CA"
                          x-text="bill.ca_number"></span>
                    <button type="button" 
                            @click.stop="copyText(bill.ca_number, bill.id)" 
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold transition border select-none active:scale-95 touch-manipulation"
                            :class="copiedCaId === bill.id ? 'bg-emerald-500/40 border-emerald-400 text-emerald-100' : 'bg-white/10 hover:bg-white/20 text-cyan-200 border-white/20'"
                            title="Copy CA to clipboard">
                        <template x-if="copiedCaId !== bill.id">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </template>
                        <template x-if="copiedCaId === bill.id">
                            <svg class="w-3 h-3 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <span x-text="copiedCaId === bill.id ? 'Copied!' : 'Copy'"></span>
                        <span x-show="copiedCaId !== bill.id" class="hidden sm:inline-block text-[8px] opacity-75 font-mono" x-text="'[' + (shortcuts.copy_ca?.toUpperCase() || 'C') + ']'"></span>
                    </button>

                    <!-- Consumer Mobile Number (Compact, Zero Size Impact, 1-Click Copy & Override) -->
                    <div class="inline-flex items-center gap-1">
                        <template x-if="bill.mobile">
                            <div class="inline-flex items-center rounded-md text-[10px] font-bold font-mono transition border select-none overflow-hidden"
                                 :class="copiedMobileId === bill.id ? 'bg-emerald-500/40 border-emerald-400 text-emerald-100' : 'bg-emerald-950/50 hover:bg-emerald-900/60 text-emerald-300 border-emerald-500/40'">
                                <button type="button" 
                                        @click.stop="copyMobile(bill)"
                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 hover:text-white transition active:scale-95 touch-manipulation cursor-pointer"
                                        :title="'Tap to copy mobile: ' + bill.mobile">
                                    <span class="text-[9px]">📱</span>
                                    <span x-text="copiedMobileId === bill.id ? 'Copied!' : bill.mobile"></span>
                                </button>
                                <button type="button"
                                        @click.stop="openMobileModal(bill)"
                                        class="px-1 py-0.5 border-l border-emerald-500/30 hover:bg-emerald-500/20 text-emerald-200 hover:text-white transition cursor-pointer"
                                        title="Edit / Override Mobile Number">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="!bill.mobile">
                            <button type="button"
                                    @click.stop="openMobileModal(bill)"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition border border-dashed border-white/20 text-slate-300 hover:text-white hover:bg-white/10 active:scale-95 select-none cursor-pointer"
                                    title="Add consumer mobile number">
                                <span class="text-[9px]">📱</span>
                                <span>+Mobile</span>
                            </button>
                        </template>
                    </div>

                    <!-- FieldDesk Bridge Badge (Module 11) - Zero Card Size Expansion -->
                    <div class="inline-flex items-center">
                        <template x-if="bill.field_desk_action">
                            <button type="button"
                                    @click.stop="openFieldDeskModal(bill)"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition border select-none active:scale-95 touch-manipulation cursor-pointer"
                                    :class="{
                                        'bg-rose-500/40 border-rose-400 text-rose-100 animate-pulse': bill.field_desk_action.is_due_today,
                                        'bg-amber-500/40 border-amber-400 text-amber-100': bill.field_desk_action.is_overdue,
                                        'bg-indigo-500/40 border-indigo-400 text-indigo-100': bill.field_desk_action.is_upcoming,
                                        'bg-emerald-500/40 border-emerald-400 text-emerald-100': bill.field_desk_action.status === 'completed'
                                    }"
                                    :title="'FieldDesk: ' + bill.field_desk_action.category_name + (bill.field_desk_action.target_date_formatted ? ' (' + bill.field_desk_action.target_date_formatted + ')' : '')">
                                <span x-text="bill.field_desk_action.category_icon || '📋'"></span>
                                <span x-text="bill.field_desk_action.is_due_today ? '🚨 Today' : (bill.field_desk_action.is_overdue ? '⚠️ Overdue' : (bill.field_desk_action.target_date_formatted || 'FieldDesk'))"></span>
                                <template x-if="bill.field_desk_action.target_amount > 0">
                                    <span class="text-[9px] opacity-90">₹<span x-text="Math.round(bill.field_desk_action.target_amount)"></span></span>
                                </template>
                            </button>
                        </template>
                        <template x-if="!bill.field_desk_action">
                            <button type="button"
                                    @click.stop="openFieldDeskModal(bill)"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition border border-dashed border-white/20 text-slate-300 hover:text-white hover:bg-white/10 active:scale-95 select-none cursor-pointer"
                                    title="Add FieldDesk Action / Follow-up [Alt+F]">
                                <span class="text-[9px]">📋</span>
                                <span>+Desk</span>
                            </button>
                        </template>
                    </div>

                    <!-- GPS Map Badge - Zero Card Size Expansion -->
                    <template x-if="bill.latitude && bill.longitude">
                        <a :href="bill.map_link || ('https://www.google.com/maps?q=' + bill.latitude + ',' + bill.longitude)"
                           target="_blank"
                           @click.stop
                           class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition bg-rose-500/30 border border-rose-400/70 text-rose-100 hover:bg-rose-500/50 hover:text-white select-none active:scale-95 cursor-pointer"
                           :title="'Open GPS coordinates in Google Maps' + (bill.location_accuracy ? ' (±' + Math.round(bill.location_accuracy) + 'm)' : '')">
                            <span class="text-[9px]">📍</span>
                            <span>Map</span>
                        </a>
                    </template>
                </div>
            </div>
        </div>

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
    </div>
</div>
