            <!-- TRUE SLIDING CARD CAROUSEL VIEW -->
            <div x-show="items.length > 0 && viewMode === 'card'"
                 :class="loading ? 'opacity-40 pointer-events-none transition-opacity duration-150' : 'opacity-100 transition-opacity duration-150'"
                 class="space-y-6">
                <!-- Slider Window / Track Container with Swipe Gestures -->
                <div class="overflow-hidden w-full max-w-lg mx-auto rounded-3xl touch-pan-y touch-pinch-zoom"
                     @touchstart="if ($event.touches && $event.touches.length > 1) { isPinching = true; } else { isPinching = false; touchStartX = $event.changedTouches[0].screenX; touchStartY = $event.changedTouches[0].screenY; }"
                     @touchend="handleTouchEnd($event)">
                    
                    <!-- Dynamic Sliding Track -->
                    <div class="flex transition-transform duration-300 ease-out will-change-transform"
                         :style="'transform: translateX(-' + (currentCardIndex * 100) + '%);'">
                        
                        <template x-for="(bill, index) in items" :key="bill.id">
                            <div class="min-w-full w-full shrink-0 px-1 box-border">
                                <div class="bg-white dark:bg-slate-900 rounded-3xl border shadow-xl overflow-hidden transition-all duration-200" :class="bill._syncError ? 'ring-2 ring-rose-500 border-rose-500 shadow-rose-500/20' : (colorSettings?.enabled ? getAvgUnitStyle(bill.smart_avg_units, 'border') : 'border-slate-200/90 dark:border-slate-800')">
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

                                    <!-- 2x2 Data Grid: The 4-Box Reading Architecture (Mobile-Optimized Single-Line Labels) -->
                                    <div class="p-3 sm:p-5 grid grid-cols-2 gap-2.5 sm:gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                                        <!-- Box 1: ✍️ Working Reading (Current Month) -->
                                        <div class="bg-white dark:bg-slate-800 p-2.5 sm:p-3 rounded-2xl border shadow-sm flex flex-col justify-between" :class="bill.pdf_sync_status === 'invalid_behind' ? 'border-rose-400 dark:border-rose-700 bg-rose-50/20' : (isBillLocked(bill) ? 'border-slate-300 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900/40' : 'border-blue-200 dark:border-blue-800/80')">
                                            <!-- Top: Header Label, Visual Badge & Lock Indicator / Shortcut -->
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-[10px] font-black uppercase tracking-wider block truncate" :class="bill.pdf_sync_status === 'invalid_behind' ? 'text-rose-600 dark:text-rose-400' : (isBillLocked(bill) ? 'text-slate-500 dark:text-slate-400' : 'text-blue-700 dark:text-cyan-300')">✍️ Working</span>
                                                    <!-- Visual Badge: Manual vs Auto -->
                                                    <span x-show="bill.working_reading" class="text-[9px] px-1.5 py-0.5 rounded-full font-bold inline-flex items-center gap-0.5 shadow-2xs"
                                                          :class="(bill.reading_source === 'manual' || bill.is_manual) ? 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700' : 'bg-blue-100 dark:bg-blue-950/80 text-blue-800 dark:text-cyan-300 border border-blue-300 dark:border-blue-700'"
                                                          x-text="(bill.reading_source === 'manual' || bill.is_manual) ? '✍️ Manual' : '⚡ Auto'"></span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <template x-if="bill.review_status === 'submitted'">
                                                        <button type="button" @click="toggleUnlockBill(bill)" 
                                                                class="text-[10px] px-1.5 py-0.5 rounded-md font-bold transition flex items-center gap-0.5 shadow-2xs"
                                                                :class="isBillLocked(bill) ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 hover:bg-emerald-200'"
                                                                :title="isBillLocked(bill) ? 'Bill is submitted (Locked). Click to unlock for editing.' : 'Bill is unlocked. Click to re-lock.'">
                                                            <span x-text="isBillLocked(bill) ? '🔒 Locked' : '🔓 Unlocked'"></span>
                                                        </button>
                                                    </template>
                                                    <span class="hidden sm:inline-block text-[9px] font-mono bg-blue-100 dark:bg-blue-950 px-1 py-0.2 rounded text-blue-700 dark:text-cyan-300 font-bold" x-text="'[' + (shortcuts.focus_reading?.toUpperCase() || 'R') + ']'"></span>
                                                </div>
                                            </div>

                                            <!-- Middle: Full-width Input -->
                                            <div class="mt-1">
                                                <input type="text" 
                                                       :id="'working-reading-input-' + bill.id"
                                                       x-model="bill.working_reading" 
                                                       @input="bill.is_manual = true; bill.reading_source = 'manual'; bill.is_projected = false"
                                                       :readonly="isBillLocked(bill)"
                                                       @blur="saveWorkingReading(bill)" 
                                                       @keydown.escape="$el.blur()"
                                                       @keyup.enter="if (!isBillLocked(bill)) { saveWorkingReading(bill); if (bill.review_status === 'submitted') { bill._unlocked = false; $el.blur(); nextCard(); } else { const wasFiltered = updateBillStatus(bill, 'submitted'); if (!wasFiltered) nextCard(); } }"
                                                       placeholder="Enter reading" 
                                                       class="w-full text-base sm:text-lg font-black border rounded-xl px-2 py-1 font-mono focus:ring-blue-500 focus:border-blue-500 text-center transition"
                                                       :class="isBillLocked(bill) ? 'bg-slate-100 dark:bg-slate-900/90 text-slate-400 dark:text-slate-500 border-slate-300 dark:border-slate-700 cursor-not-allowed' : (bill.pdf_sync_status === 'invalid_behind' ? 'border-rose-400 text-rose-600 dark:text-rose-400 bg-blue-50/40 dark:bg-slate-900/60' : 'border-blue-200 dark:border-blue-800 text-blue-600 dark:text-cyan-400 bg-blue-50/40 dark:bg-slate-900/60')">
                                            </div>

                                            <!-- Bottom / Downline: Left (Diff) | Center (🚨 < PDF!) | Right (Auto-fill) -->
                                            <div class="mt-1 flex items-center justify-between text-[10px] border-t border-slate-100 dark:border-slate-700/60 pt-1 gap-1">
                                                <span class="text-slate-700 dark:text-slate-300 font-mono font-bold shrink-0 truncate" x-text="'Diff: ' + (bill.working_diff_units ?? 0)"></span>
                                                <div class="text-center truncate">
                                                    <template x-if="bill.pdf_sync_status === 'invalid_behind'">
                                                        <span class="text-[8px] sm:text-[9px] font-black text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-950/80 px-1 py-0.2 rounded animate-pulse">🚨 &lt; PDF!</span>
                                                    </template>
                                                </div>
                                                <div class="text-right shrink-0 flex items-center gap-1">
                                                    <button @click="autoFillWorkingReading(bill)" 
                                                            :disabled="isBillLocked(bill)"
                                                            class="text-[9px] sm:text-[10px] font-bold flex items-center gap-0.5 transition"
                                                            :class="isBillLocked(bill) ? 'opacity-40 cursor-not-allowed text-slate-400' : 'text-blue-600 dark:text-cyan-400 hover:text-blue-800 dark:hover:text-cyan-300 hover:underline'"
                                                            title="Auto-fill with Prev + Avg (enforcing >= PDF)">
                                                        <span>⚡ Auto</span>
                                                        <span class="hidden sm:inline-block text-[8px] font-mono opacity-75" x-text="'[' + (shortcuts.auto_fill_reading?.toUpperCase() || 'A') + ']'"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Box 2: 📅 Previous Reading (DB) -->
                                        <div class="bg-white dark:bg-slate-800 p-2.5 sm:p-3 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm flex flex-col justify-between">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate">📅 Prev (DB)</span>
                                            <div class="text-base sm:text-lg font-black text-slate-700 dark:text-slate-200 my-0.5 sm:my-1 font-mono text-center" x-text="bill.db_prev_reading ?? '—'"></div>
                                            <div class="text-[9px] sm:text-[10px] text-slate-400 border-t border-slate-100 dark:border-slate-700/60 pt-1 truncate" x-text="bill.db_prev_label ? (bill.db_prev_label.startsWith('From ') ? bill.db_prev_label : 'From: ' + bill.db_prev_label) : 'Baseline'"></div>
                                        </div>

                                        <!-- Box 3: 📊 Average Usage (Avg kWh) -->
                                        <div @click="openMeterHistoryModal(bill.ca_number, bill.consumer_name)" 
                                             class="bg-white dark:bg-slate-800 p-2.5 sm:p-3 rounded-2xl border shadow-sm flex flex-col justify-between cursor-pointer transition group" 
                                             :class="colorSettings?.enabled ? getAvgUnitStyle(bill.smart_avg_units, 'border') : 'border-slate-200/80 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-500'" 
                                             title="Click to view 2D Monthly Reading History & calculation audit">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate">📊 Avg Usage</span>
                                                <span class="text-[9px] text-indigo-500 font-bold opacity-70 group-hover:opacity-100 transition">History ↗</span>
                                            </div>
                                            <div class="text-base sm:text-lg my-0.5 sm:my-1 font-mono text-center transition" 
                                                 :class="getAvgUnitStyle(bill.smart_avg_units)" 
                                                 x-text="(bill.smart_avg_units ?? 50) + ' kWh'"></div>
                                            <div class="text-[9px] sm:text-[10px] text-slate-400 border-t border-slate-100 dark:border-slate-700/60 pt-1 truncate" x-text="bill.smart_avg_label || 'History Avg'"></div>
                                        </div>

                                        <!-- Box 4: 📄 Official PDF Reading -->
                                        <div class="bg-white dark:bg-slate-800 p-2.5 sm:p-3 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm flex flex-col justify-between">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate">📄 PDF Read</span>
                                            <div class="text-base sm:text-lg font-black my-0.5 sm:my-1 font-mono text-center" :class="bill.official_pdf_reading ? 'text-slate-800 dark:text-white' : 'text-slate-400'" x-text="bill.official_pdf_reading ?? '—'"></div>
                                            <div class="border-t border-slate-100 dark:border-slate-700/60 pt-1 truncate">
                                                <template x-if="bill.pdf_sync_status === 'ahead'">
                                                    <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-1 sm:px-1.5 py-0.2 rounded" x-text="'⚡ +' + (bill.pdf_delta ?? 0) + ' Ahead'"></span>
                                                </template>
                                                <template x-if="bill.pdf_sync_status === 'matched'">
                                                    <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-bold text-blue-600 dark:text-cyan-400 bg-blue-50 dark:bg-blue-950/60 px-1 sm:px-1.5 py-0.2 rounded">✅ Exact Match</span>
                                                </template>
                                                <template x-if="bill.pdf_sync_status === 'invalid_behind'">
                                                    <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-black text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-950 px-1 sm:px-1.5 py-0.2 rounded animate-pulse" x-text="'🚨 ' + (bill.pdf_delta ?? 0) + ' Behind!'"></span>
                                                </template>
                                                <template x-if="bill.pdf_sync_status === 'awaiting'">
                                                    <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-1 sm:px-1.5 py-0.2 rounded">⏳ Awaiting</span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Action Buttons (Mobile-Optimized Clean Widths) -->
                                    <div class="px-3 sm:px-5 py-2.5 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                                        <button @click="updateBillStatus(bill, 'submitted')" :class="bill.review_status === 'submitted' ? 'bg-emerald-600 text-white shadow-md ring-2 ring-emerald-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 hover:text-emerald-700'" class="flex-1 py-2 sm:py-2.5 rounded-xl font-bold text-xs transition active:scale-95 flex items-center justify-center gap-1">
                                            <span>✅ Submitted</span>
                                            <span class="hidden sm:inline-block text-[9px] font-mono opacity-60 bg-black/10 dark:bg-white/10 px-1 rounded" x-text="'[' + (shortcuts.submit_ok || 'Enter') + ']'"></span>
                                        </button>
                                        <button @click="updateBillStatus(bill, 'critical')" :class="bill.review_status === 'critical' ? 'bg-rose-600 text-white shadow-md ring-2 ring-rose-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-rose-50 dark:hover:bg-rose-950/50 hover:text-rose-700'" class="flex-1 py-2 sm:py-2.5 rounded-xl font-bold text-xs transition active:scale-95 flex items-center justify-center gap-1">
                                            <span>❌ Critical</span>
                                            <span class="hidden sm:inline-block text-[9px] font-mono opacity-60 bg-black/10 dark:bg-white/10 px-1 rounded" x-text="'[' + (shortcuts.mark_critical || '3') + ']'"></span>
                                        </button>
                                        <button @click="updateBillStatus(bill, 'doubt')" :class="bill.review_status === 'doubt' ? 'bg-amber-600 text-white shadow-md ring-2 ring-amber-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-amber-50 dark:hover:bg-amber-950/50 hover:text-amber-700'" class="flex-1 py-2 sm:py-2.5 rounded-xl font-bold text-xs transition active:scale-95 flex items-center justify-center gap-1">
                                            <span>⚠️ Doubt</span>
                                            <span class="hidden sm:inline-block text-[9px] font-mono opacity-60 bg-black/10 dark:bg-white/10 px-1 rounded" x-text="'[' + (shortcuts.mark_doubt || '2') + ']'"></span>
                                        </button>
                                    </div>

                                    <!-- Remark / Notes Section (Clean & Compact) -->
                                    <div class="px-3 sm:px-5 py-3 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200/80 dark:border-slate-700 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <label class="text-xs font-bold text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                                <span>💬</span> Remark / Note:
                                                <span class="hidden sm:inline-block text-[10px] text-slate-400 font-mono" x-text="'[' + (shortcuts.open_remark?.toUpperCase() || 'M') + ']'"></span>
                                            </label>
                                            <div class="flex items-center gap-1">
                                                <button @click="saveBillRemark(bill, true)" class="px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-bold shadow-xs transition flex items-center gap-1">
                                                    <span>💾 Save</span>
                                                    <span class="hidden sm:inline-block text-[8px] font-mono opacity-70">[Ctrl+↵]</span>
                                                </button>
                                                <button @click="clearBillRemark(bill)" class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-[10px] font-semibold transition">
                                                    🗑 Clear
                                                </button>
                                            </div>
                                        </div>

                                        <textarea :id="'remark-input-' + bill.id"
                                                  x-model="bill.remark" 
                                                  @focus="onRemarkFocus(bill)"
                                                  @blur="onRemarkBlur(bill)"
                                                  @keydown.escape="$el.blur()"
                                                  @keydown.ctrl.enter="saveBillRemark(bill, true); $el.blur();"
                                                  @keydown.meta.enter="saveBillRemark(bill, true); $el.blur();"
                                                  rows="2" 
                                                  placeholder="Add observation note..." 
                                                  class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-600 p-2 bg-white dark:bg-slate-900 text-slate-800 dark:text-white focus:ring-blue-500 focus:border-blue-500"></textarea>

                                        <!-- Optional Quick Presets (Only displayed if enabled in preferences) -->
                                        <div x-show="showRemarkPresets" class="flex flex-wrap items-center gap-1.5 pt-0.5" x-cloak>
                                            <button type="button" @click="bill.remark = 'Door Locked'; saveBillRemark(bill, true);" class="px-2 py-0.5 rounded-lg text-[9px] font-semibold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">🚪 Door Locked</button>
                                            <button type="button" @click="bill.remark = 'Meter Burnt'; saveBillRemark(bill, true);" class="px-2 py-0.5 rounded-lg text-[9px] font-semibold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">🔥 Meter Burnt</button>
                                            <button type="button" @click="bill.remark = 'Meter Stopped'; saveBillRemark(bill, true);" class="px-2 py-0.5 rounded-lg text-[9px] font-semibold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">⛔ Meter Stopped</button>
                                            <button type="button" @click="bill.remark = 'High Usage'; saveBillRemark(bill, true);" class="px-2 py-0.5 rounded-lg text-[9px] font-semibold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">📈 High Usage</button>
                                            <button type="button" @click="bill.remark = 'Verified OK'; saveBillRemark(bill, true);" class="px-2 py-0.5 rounded-lg text-[9px] font-semibold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">✅ Verified OK</button>
                                        </div>
                                    </div>

                                    <!-- Tag Selection Section (Clean, Focused, Responsive Mobile & Desktop) -->
                                    <div class="px-3 sm:px-5 py-2.5 bg-slate-100/70 dark:bg-slate-800/50 border-t border-slate-200/80 dark:border-slate-700 space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                                <span>🏷️</span> Tag:
                                            </label>
                                            <!-- Active Tag Indicator Badge -->
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all"
                                                  :class="getTagBadgeClass(bill.tag || defaultTag)"
                                                  x-text="getTagDisplayLabel(bill.tag || defaultTag)"
                                                  :title="getTagFullLabel(bill.tag || defaultTag)">
                                            </span>
                                        </div>

                                        <!-- Responsive Tag Pills Selection Bar -->
                                        <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                            <template x-for="tagItem in availableTags" :key="tagItem.code">
                                                <button type="button" 
                                                        @click="setBillTag(bill, tagItem.code)"
                                                        :title="tagItem.label"
                                                        class="px-2.5 py-1 rounded-xl text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer border shadow-xs"
                                                        :class="(bill.tag === tagItem.code || (!bill.tag && tagItem.code === defaultTag)) 
                                                            ? getActivePillClass(tagItem.color) 
                                                            : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-slate-400 dark:hover:border-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                                                    <span x-show="(bill.tag === tagItem.code || (!bill.tag && tagItem.code === defaultTag))" class="text-[9px]">✓</span>
                                                    <span x-text="tagItem.short_label || tagItem.label"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Card Footer -->
                                    <div class="px-4 sm:px-5 py-2.5 bg-slate-900 dark:bg-slate-950 text-white flex items-center justify-between text-xs font-medium border-t border-slate-800">
                                        <span class="flex items-center gap-1.5 text-cyan-300 font-bold font-mono text-[11px]">
                                            ⚡ Meter: <span class="text-white select-text select-all cursor-pointer hover:underline" @click="if (bill.meter_no) copyText(bill.meter_no)" title="Tap to copy or select meter number" x-text="bill.meter_no || '—'"></span>
                                        </span>
                                        <div class="flex items-center gap-2">
                                            <template x-if="bill.has_pdf">
                                                <div class="flex items-center gap-2">
                                                    <button @click="openPdfModal(bill)" class="text-cyan-400 hover:text-white font-bold flex items-center gap-1 text-[11px] transition">
                                                        📄 View PDF →
                                                    </button>
                                                    <button @click="downloadSingleBill(bill)" :disabled="syncingSingle === bill.ca_number" class="p-1 text-slate-400 hover:text-cyan-300 transition" title="Re-download / Refresh Bill">
                                                        <span x-show="syncingSingle !== bill.ca_number">⚡</span>
                                                        <svg x-show="syncingSingle === bill.ca_number" class="w-3.5 h-3.5 animate-spin text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="!bill.has_pdf">
                                                <button @click="downloadSingleBill(bill)" :disabled="syncingSingle === bill.ca_number" class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white font-bold text-[10px] rounded-lg transition flex items-center gap-1">
                                                    <span x-show="syncingSingle !== bill.ca_number">⚡ Download</span>
                                                    <span x-show="syncingSingle === bill.ca_number" class="flex items-center gap-1">
                                                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    </span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                    </div>
                </div>

                <!-- Navigation Controller (Placed AFTER card info) -->
                <div class="flex flex-col gap-2 max-w-lg mx-auto">
                    <div class="flex items-center justify-between bg-white dark:bg-slate-900 px-6 py-3.5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <button @click="prevCard()" :disabled="currentCardIndex <= 0" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed text-slate-700 dark:text-slate-200 rounded-2xl font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Prev Card
                        </button>

                        <div class="text-center">
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1.5">
                                <span>Slide Counter</span>
                                <span x-show="loadingMoreCards" class="inline-flex items-center gap-1 text-[10px] text-blue-500 font-semibold animate-pulse" title="Loading more cards in background...">
                                    <svg class="animate-spin w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Loading...</span>
                                </span>
                            </div>
                            <div class="text-sm font-black text-slate-900 dark:text-white mt-0.5">
                                <span class="text-blue-600 dark:text-cyan-400 font-mono" x-text="items.length > 0 ? (currentCardIndex + 1) : 0"></span>
                                <span class="text-slate-400">/</span>
                                <span class="text-slate-600 dark:text-slate-300 font-mono" x-text="pagination.total || items.length"></span>
                            </div>
                        </div>

                        <button @click="nextCard()" :disabled="currentCardIndex >= items.length - 1 && (!pagination.last_page || pagination.current_page >= pagination.last_page)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-30 disabled:cursor-not-allowed text-white rounded-2xl font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-blue-500/20">
                            Next Card
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    <!-- Subtle deck progress line -->
                    <div class="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-blue-600 dark:bg-cyan-500 h-full transition-all duration-300 rounded-full"
                             :style="'width: ' + (items.length > 0 ? Math.min(100, Math.round(((currentCardIndex + 1) / (pagination.total || items.length)) * 100)) : 0) + '%;'">
                        </div>
                    </div>
                </div>

                <!-- Slide Navigation Dots (dynamic sliding window around current card) -->
                <div class="flex flex-wrap items-center justify-center gap-1.5 max-w-md mx-auto pt-1">
                    <template x-for="dot in getVisibleCardDots()" :key="dot.id">
                        <button @click="currentCardIndex = dot.index"
                                :class="dot.index === currentCardIndex ? 'bg-blue-600 dark:bg-cyan-400 w-6 h-2 rounded-full shadow-sm' : 'bg-slate-300 dark:bg-slate-700 hover:bg-slate-400 w-2 h-2 rounded-full'"
                                class="transition-all duration-200 focus:outline-none"
                                :title="'Go to card ' + (dot.index + 1)">
                        </button>
                    </template>
                </div>
            </div>
