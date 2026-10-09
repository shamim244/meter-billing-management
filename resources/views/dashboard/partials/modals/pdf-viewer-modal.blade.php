            <!-- MODAL 3: In-App Sequential PDF Viewer -->
            <div x-show="showPdfViewerModal" x-cloak class="fixed inset-0 z-50 overflow-hidden bg-slate-950/80 backdrop-blur-md flex flex-col p-2 sm:p-4">
                <div @click.outside="showPdfViewerModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-5xl h-full mx-auto flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <!-- Header -->
                    <div class="p-3.5 sm:px-6 sm:py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/80 flex flex-wrap items-center justify-between gap-3 shrink-0">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-cyan-300 font-black text-xs sm:text-sm flex items-center justify-center font-mono shrink-0" x-text="activePdfBill?.ca_number ? activePdfBill.ca_number.slice(-2) : '📄'"></div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                                    <span class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate" x-text="activePdfBill?.consumer_name || 'Consumer Bill'"></span>
                                    <span class="font-mono text-[11px] sm:text-xs text-blue-600 dark:text-cyan-400 font-bold" x-text="'CA: ' + (activePdfBill?.ca_number || '—')"></span>
                                </div>
                                <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                    <span x-text="'Period: ' + (activePdfBill?.bill_month_label || (activePdfBill?.billing_month + '/' + activePdfBill?.billing_year))"></span>
                                    <span>•</span>
                                    <span class="font-bold" :class="Number(activePdfBill?.total_amount) < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-800 dark:text-slate-200'" x-text="formatCurrency(activePdfBill?.total_amount)"></span>
                                    <span>•</span>
                                    <span x-text="(activePdfBill?.units_consumed ?? 0) + ' kWh'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Controls -->
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <!-- Status Switcher in Modal -->
                            <div class="hidden sm:inline-flex items-center gap-1 bg-white dark:bg-slate-900 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                                <button @click="updateBillStatus(activePdfBill, 'submitted')" :class="activePdfBill?.review_status === 'submitted' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-2.5 py-1 rounded-lg text-xs transition" title="Mark Submitted">✅</button>
                                <button @click="updateBillStatus(activePdfBill, 'critical')" :class="activePdfBill?.review_status === 'critical' ? 'bg-rose-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-2.5 py-1 rounded-lg text-xs transition" title="Mark Critical">❌</button>
                                <button @click="updateBillStatus(activePdfBill, 'doubt')" :class="activePdfBill?.review_status === 'doubt' ? 'bg-amber-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-2.5 py-1 rounded-lg text-xs transition" title="Mark Doubt">⚠️</button>
                            </div>

                            <!-- Sequential Navigation: Prev / Next Bill -->
                            <div class="inline-flex items-center bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 p-1">
                                <button @click="navigatePdfBill(-1)" :disabled="getPdfBillIndex() <= 0" class="px-2 sm:px-2.5 py-1 text-xs font-bold text-slate-700 dark:text-slate-200 disabled:opacity-30 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition flex items-center gap-1" title="Previous Bill">
                                    ⟨
                                </button>
                                <span class="px-1.5 sm:px-2 text-[11px] font-mono text-slate-400" x-text="(getPdfBillIndex() + 1) + '/' + items.length"></span>
                                <button @click="navigatePdfBill(1)" :disabled="getPdfBillIndex() >= items.length - 1" class="px-2 sm:px-2.5 py-1 text-xs font-bold text-slate-700 dark:text-slate-200 disabled:opacity-30 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition flex items-center gap-1" title="Next Bill">
                                    ⟩
                                </button>
                            </div>

                            <!-- Print & Re-download & Delete -->
                            <button @click="printPdfIframe()" class="p-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition" title="Print PDF">
                                🖨️
                            </button>
                            <button @click="downloadSingleBill(activePdfBill)" :disabled="syncingSingle === activePdfBill?.ca_number" class="p-2 bg-blue-50 dark:bg-slate-800 hover:bg-blue-100 dark:hover:bg-slate-700 text-blue-600 dark:text-cyan-400 rounded-xl text-xs font-bold border border-blue-200 dark:border-slate-700 transition" title="Re-download / Refresh this Bill">
                                <span x-show="syncingSingle !== activePdfBill?.ca_number">⚡</span>
                                <svg x-show="syncingSingle === activePdfBill?.ca_number" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </button>
                            <button @click="deleteBillPdf(activePdfBill)" class="p-2 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-800 transition" title="Delete PDF file from storage and reset status">
                                🗑️
                            </button>
                            <a :href="activePdfBill?.id ? ('/bills/pdf/' + activePdfBill.id) : '#'" target="_blank" class="p-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition" title="Open in New Tab">
                                ↗
                            </a>

                            <!-- Close -->
                            <button @click="showPdfViewerModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-xl">
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Embedded PDF Iframe -->
                    <div class="flex-1 bg-slate-100 dark:bg-slate-950 relative overflow-hidden">
                        <template x-if="activePdfBill?.id">
                            <iframe id="pdfViewerIframe" :src="'/bills/pdf/' + activePdfBill.id" class="w-full h-full border-0"></iframe>
                        </template>
                    </div>
                </div>
            </div>
