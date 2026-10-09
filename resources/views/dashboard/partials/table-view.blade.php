            <!-- TABLE VIEW -->
            <div x-show="!loading && items.length > 0 && viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">
                            <tr>
                                <th class="py-3.5 px-3 cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('ca_number')">
                                    <div class="inline-flex items-center gap-1">
                                        <span>Consumer</span>
                                        <span class="text-[10px]" x-show="sortCol === 'ca_number'" x-text="sortAsc ? '▲' : '▼'"></span>
                                    </div>
                                </th>
                                <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('billing_basis')">
                                    <div class="inline-flex items-center justify-center gap-1">
                                        <span>Basis</span>
                                        <span class="text-[10px]" x-show="sortCol === 'billing_basis' || sortCol === 'basis_priority'" x-text="sortAsc ? '▲' : '▼'"></span>
                                    </div>
                                </th>
                                <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('working_reading')">
                                    <div class="inline-flex items-center justify-center gap-1">
                                        <span>✍️ Working Reading</span>
                                        <span class="text-[10px]" x-show="sortCol === 'working_reading'" x-text="sortAsc ? '▲' : '▼'"></span>
                                    </div>
                                </th>
                                <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('previous_reading')">
                                    <div class="inline-flex items-center justify-center gap-1">
                                        <span>📅 Prev (DB)</span>
                                        <span class="text-[10px]" x-show="sortCol === 'previous_reading'" x-text="sortAsc ? '▲' : '▼'"></span>
                                    </div>
                                </th>
                                <th class="py-3.5 px-3 text-center">📊 Avg (kWh)</th>
                                <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('current_reading')">
                                    <div class="inline-flex items-center justify-center gap-1">
                                        <span>📄 PDF Read</span>
                                        <span class="text-[10px]" x-show="sortCol === 'current_reading'" x-text="sortAsc ? '▲' : '▼'"></span>
                                    </div>
                                </th>
                                <th class="py-3.5 px-3 text-right cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('amount')">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <span>Amount</span>
                                        <span class="text-[10px]" x-show="sortCol === 'amount' || sortCol === 'total_amount'" x-text="sortAsc ? '▲' : '▼'"></span>
                                    </div>
                                </th>
                                <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('bill_month')">
                                    <div class="inline-flex items-center justify-center gap-1">
                                        <span>Month</span>
                                        <span class="text-[10px]" x-show="sortCol === 'bill_month'" x-text="sortAsc ? '▲' : '▼'"></span>
                                    </div>
                                </th>
                                <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('review_status')">
                                    <div class="inline-flex items-center justify-center gap-1">
                                        <span>Status</span>
                                        <span class="text-[10px]" x-show="sortCol === 'review_status' || sortCol === 'status'" x-text="sortAsc ? '▲' : '▼'"></span>
                                    </div>
                                </th>
                                <th class="py-3.5 px-3 text-center">Tag</th>
                                <th class="py-3.5 px-3">Remark</th>
                                <th class="py-3.5 px-3 text-center">PDF</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                            <template x-for="(bill, index) in items" :key="bill.id">
                                <tr :id="'row-' + bill.ca_number" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition duration-150" :class="{
                                    'ring-2 ring-rose-500 bg-rose-50/50 dark:bg-rose-950/40': bill._syncError,
                                    'bg-emerald-50/30 dark:bg-emerald-950/25': bill.review_status === 'submitted' && !bill._syncError,
                                    'bg-rose-50/30 dark:bg-rose-950/25': bill.review_status === 'critical' && !bill._syncError,
                                    'bg-amber-50/30 dark:bg-amber-950/25': bill.review_status === 'doubt' && !bill._syncError
                                }">
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

                                    <!-- Basis Badge Column -->
                                    <td class="py-3 px-2 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black font-mono inline-block shadow-2xs"
                                              :class="{
                                                  'bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/80': (bill.billing_basis || 'OK') === 'OK',
                                                  'bg-amber-50 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/80': bill.billing_basis === 'LK',
                                                  'bg-rose-50 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800/80': bill.billing_basis === 'MD',
                                                  'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/80': bill.billing_basis === 'PL',
                                                  'bg-purple-50 dark:bg-purple-950/70 text-purple-700 dark:text-purple-300 border border-purple-200/80 dark:border-purple-800/80': bill.billing_basis === 'RN'
                                              }"
                                              :title="'Basis: ' + (bill.billing_basis || 'OK')"
                                              x-text="bill.billing_basis || 'OK'">
                                        </span>
                                    </td>

                                    <!-- ✍️ Working Reading (Current Month) -->
                                    <td class="py-3 px-2 text-center">
                                        <div class="inline-flex items-center gap-1 justify-center">
                                            <template x-if="bill.review_status === 'submitted'">
                                                <button type="button" @click="toggleUnlockBill(bill)"
                                                        class="text-[11px] p-0.5 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                                        :title="isBillLocked(bill) ? 'Bill submitted (Locked). Click to unlock' : 'Bill unlocked. Click to re-lock'">
                                                    <span x-text="isBillLocked(bill) ? '🔒' : '🔓'"></span>
                                                </button>
                                            </template>
                                            <input type="text" 
                                                   :id="'working-reading-input-table-' + bill.id"
                                                   x-model="bill.working_reading" 
                                                   @input="bill.is_manual = true; bill.reading_source = 'manual'; bill.is_projected = false"
                                                   :readonly="isBillLocked(bill)"
                                                   @blur="saveWorkingReading(bill)" 
                                                   @keyup.enter="$event.target.blur()"
                                                   class="w-20 text-center font-mono font-bold text-xs rounded-lg py-1 px-1 focus:ring-blue-500 transition"
                                                   :class="isBillLocked(bill) ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-not-allowed' : 'border-blue-200 dark:border-blue-800 bg-blue-50/40 dark:bg-slate-800 text-blue-600 dark:text-cyan-400'" />
                                            <button @click="autoFillWorkingReading(bill)" 
                                                    :disabled="isBillLocked(bill)"
                                                    class="text-[9px] px-1 py-0.5 rounded font-bold transition"
                                                    :class="isBillLocked(bill) ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600' : 'bg-blue-50 dark:bg-blue-900/60 text-blue-600 dark:text-cyan-300 hover:bg-blue-100'"
                                                    title="Auto-fill Prev + Avg">⚡</button>
                                        </div>
                                        <div class="flex items-center justify-center gap-1 text-[10px] text-slate-400 font-mono mt-0.5">
                                            <span x-text="'Diff: ' + (bill.working_diff_units ?? 0) + 'k'"></span>
                                            <span x-show="bill.working_reading" class="text-[9px] px-1 py-0.2 rounded font-bold inline-flex items-center"
                                                  :class="(bill.reading_source === 'manual' || bill.is_manual) ? 'bg-amber-100 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-blue-100 dark:bg-blue-950/70 text-blue-700 dark:text-cyan-300 border border-blue-200 dark:border-blue-800'"
                                                  :title="(bill.reading_source === 'manual' || bill.is_manual) ? 'Manual Custom Override' : 'Auto / Projected Reading'"
                                                  x-text="(bill.reading_source === 'manual' || bill.is_manual) ? '✍️' : '⚡'"></span>
                                        </div>
                                    </td>

                                    <!-- 📅 Previous Reading (DB) -->
                                    <td class="py-3 px-2 text-center font-mono text-xs text-slate-700 dark:text-slate-300">
                                        <div class="font-bold" x-text="bill.db_prev_reading ?? '—'"></div>
                                        <div class="text-[9px] text-slate-400 truncate max-w-[80px]" x-text="bill.db_prev_label || ''"></div>
                                    </td>

                                    <!-- 📊 Average Usage (Avg kWh) -->
                                    <td class="py-3 px-2 text-center font-mono text-xs cursor-pointer hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40 transition group rounded-xl"
                                        @click="openMeterHistoryModal(bill.ca_number, bill.consumer_name)"
                                        title="Click to view 2D Monthly Reading History & calculation breakdown">
                                        <div class="transition" :class="getAvgUnitStyle(bill.smart_avg_units)" x-text="(bill.smart_avg_units ?? 50) + ' k'"></div>
                                        <div class="text-[9px] text-indigo-500 font-semibold opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-0.5">
                                            <span>📊</span> History
                                        </div>
                                    </td>

                                    <!-- 📄 Official PDF Reading -->
                                    <td class="py-3 px-2 text-center font-mono text-xs">
                                        <template x-if="bill.official_pdf_reading">
                                            <div>
                                                <div class="font-bold text-slate-800 dark:text-white" x-text="bill.official_pdf_reading"></div>
                                                <template x-if="bill.pdf_sync_status === 'ahead'">
                                                    <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-1 py-0.2 rounded" x-text="'+' + (bill.pdf_delta ?? 0) + 'k'"></span>
                                                </template>
                                                <template x-if="bill.pdf_sync_status === 'matched'">
                                                    <span class="text-[9px] font-bold text-blue-600 dark:text-cyan-400 bg-blue-50 dark:bg-blue-950/60 px-1 py-0.2 rounded">Match</span>
                                                </template>
                                                <template x-if="bill.pdf_sync_status === 'invalid_behind'">
                                                    <span class="text-[9px] font-black text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-950 px-1 py-0.2 rounded animate-pulse" x-text="'🚨 ' + (bill.pdf_delta ?? 0) + 'k'"></span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!bill.official_pdf_reading">
                                            <span class="text-slate-400 text-[10px]">⏳ Awaiting</span>
                                        </template>
                                    </td>

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
                                            <button @click="updateBillStatus(bill, 'critical')" :class="bill.review_status === 'critical' ? 'bg-rose-600 text-white shadow-sm ring-2 ring-rose-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-rose-100 dark:hover:bg-rose-900/50 hover:text-rose-700'" class="px-2.5 py-1 rounded-lg text-xs transition active:scale-95" title="Mark Critical">
                                                ❌
                                            </button>
                                            <button @click="updateBillStatus(bill, 'doubt')" :class="bill.review_status === 'doubt' ? 'bg-amber-600 text-white shadow-sm ring-2 ring-amber-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-amber-100 dark:hover:bg-amber-900/50 hover:text-amber-700'" class="px-2.5 py-1 rounded-lg text-xs transition active:scale-95" title="Mark Doubt">
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
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Table Pagination -->
                <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
                    <div class="text-slate-500 dark:text-slate-400 font-medium">
                        Showing <span class="font-bold text-slate-800 dark:text-white" x-text="items.length > 0 ? (pagination.from || 1) : 0"></span> to <span class="font-bold text-slate-800 dark:text-white" x-text="items.length > 0 ? Math.min((pagination.to || items.length), items.length) : 0"></span> of <span class="font-bold text-slate-800 dark:text-white" x-text="pagination.total"></span> records
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="fetchData(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-3 py-1 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg font-bold text-slate-700 dark:text-slate-200 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-600 transition">
                            ⟨ Prev
                        </button>
                        <span class="px-3 py-1 font-bold text-slate-800 dark:text-white" x-text="'Page ' + pagination.current_page + ' of ' + pagination.last_page"></span>
                        <button @click="fetchData(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-3 py-1 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg font-bold text-slate-700 dark:text-slate-200 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-600 transition">
                            Next ⟩
                        </button>
                    </div>
                </div>
            </div>
