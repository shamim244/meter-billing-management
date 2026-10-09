<!-- Consumer CA & Name -->
<td class="py-3 px-3">
    <div class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 flex items-center justify-center font-bold text-[11px] font-mono shrink-0" x-text="bill.ca_number.slice(-2)"></div>
        <div>
            <div class="flex items-center gap-1 flex-wrap">
                <a :href="'/bills/history/' + bill.ca_number" class="font-bold text-blue-600 dark:text-cyan-400 hover:underline font-mono text-xs select-text select-all" x-text="bill.ca_number"></a>
                <button type="button" @click="copyText(bill.ca_number, bill.id)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 transition" title="Copy CA">
                    <template x-if="copiedCaId !== bill.id">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </template>
                    <template x-if="copiedCaId === bill.id">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </template>
                </button>
                <span x-show="bill.tariff_category" class="px-1 py-0.2 rounded text-[9px] font-bold font-mono bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200/50 dark:border-indigo-800/50" x-text="bill.tariff_category"></span>
                <span x-show="bill.billing_basis" class="px-1 py-0.2 rounded text-[9px] font-black font-mono" :class="bill.billing_basis === 'OK' ? 'bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200/50 dark:border-emerald-800/50' : (bill.billing_basis === 'MD' ? 'bg-amber-50 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border border-amber-200/50 dark:border-amber-800/50' : 'bg-rose-50 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 border border-rose-200/50 dark:border-rose-800/50')" :title="'Billing Basis: ' + bill.billing_basis" x-text="bill.billing_basis"></span>
                <template x-if="bill.is_consecutive_alert">
                    <span class="px-1 py-0.2 rounded text-[9px] font-black uppercase bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800"
                          :title="bill.consecutive_count + ' Consecutive Estimated Cycles (' + bill.billing_basis + ')'"
                          x-text="'⚠️ ' + bill.consecutive_count + 'x ' + bill.billing_basis">
                    </span>
                </template>
                <template x-if="isCaPendingSync(bill.ca_number)">
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-200 border border-amber-300 dark:border-amber-800" title="Changes saved locally on device, waiting to sync with server">
                        ☁️ Offline
                    </span>
                </template>
                <template x-if="bill._syncError">
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-rose-600 text-white shadow-2xs animate-pulse" :title="bill._syncErrorMsg || 'Server rejected update'">
                        ⚠️ Reverted
                    </span>
                </template>
            </div>
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-slate-900 dark:text-white font-semibold truncate max-w-[140px] text-xs" x-text="bill.consumer_name || '—'"></span>
                <template x-if="bill.mobile">
                    <span class="inline-flex items-center rounded text-[10px] font-mono font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <button type="button" @click.stop="copyMobile(bill)" class="px-1.5 py-0.5 hover:underline inline-flex items-center gap-0.5 cursor-pointer" :title="'Copy mobile: ' + bill.mobile">
                            <span class="text-[9px]">📱</span>
                            <span x-text="copiedMobileId === bill.id ? 'Copied!' : bill.mobile"></span>
                        </button>
                        <button type="button" @click.stop="openMobileModal(bill)" class="px-1 py-0.5 border-l border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 cursor-pointer" title="Edit Mobile">
                            ✏️
                        </button>
                    </span>
                </template>
                <template x-if="!bill.mobile">
                    <button type="button" @click.stop="openMobileModal(bill)" class="text-[9px] text-slate-400 hover:text-blue-600 dark:hover:text-cyan-400 font-mono hover:underline inline-flex items-center gap-0.5 cursor-pointer" title="Add Mobile Number">
                        <span>📱</span>
                        <span>+Add</span>
                    </button>
                </template>
                <template x-if="bill.field_desk_action">
                    <button type="button" @click.stop="openFieldDeskModal(bill)" class="ml-1 text-[9px] font-mono px-1.5 py-0.2 rounded font-bold inline-flex items-center gap-0.5 cursor-pointer border"
                            :class="{
                                'bg-rose-500/20 border-rose-400 text-rose-600 dark:text-rose-300 animate-pulse': bill.field_desk_action.is_due_today,
                                'bg-amber-500/20 border-amber-400 text-amber-600 dark:text-amber-300': bill.field_desk_action.is_overdue,
                                'bg-blue-500/20 border-blue-400 text-blue-600 dark:text-blue-300': bill.field_desk_action.is_upcoming,
                                'bg-emerald-500/20 border-emerald-400 text-emerald-600 dark:text-emerald-300': bill.field_desk_action.status === 'completed'
                            }">
                        <span x-text="bill.field_desk_action.category_icon || '📋'"></span>
                        <span x-text="bill.field_desk_action.is_due_today ? 'Today' : (bill.field_desk_action.is_overdue ? 'Overdue' : bill.field_desk_action.target_date_formatted)"></span>
                    </button>
                </template>
                <template x-if="bill.latitude && bill.longitude">
                    <a :href="bill.map_link || ('https://www.google.com/maps?q=' + bill.latitude + ',' + bill.longitude)"
                       target="_blank"
                       @click.stop
                       class="ml-1 text-[9px] font-mono px-1.5 py-0.2 rounded font-bold inline-flex items-center gap-0.5 bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-900/60 cursor-pointer"
                       :title="'Open GPS Location in Google Maps' + (bill.location_accuracy ? ' (±' + Math.round(bill.location_accuracy) + 'm)' : '')">
                        <span>📍</span>
                        <span>Map</span>
                    </a>
                </template>
            </div>
        </div>
    </div>
</td>
