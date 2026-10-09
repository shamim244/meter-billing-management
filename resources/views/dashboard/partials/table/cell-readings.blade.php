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
