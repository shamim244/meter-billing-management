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
