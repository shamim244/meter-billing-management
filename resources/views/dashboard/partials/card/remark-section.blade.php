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
