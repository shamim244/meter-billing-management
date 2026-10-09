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
