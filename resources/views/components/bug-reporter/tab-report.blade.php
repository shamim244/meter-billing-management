{{-- Tab 1: Report Issue Form --}}
<div x-show="activeTab === 'report'" class="p-6 overflow-y-auto space-y-4">
    <!-- Success Message Banner -->
    <div x-show="submittedCode" x-cloak class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 space-y-2">
        <div class="flex items-center gap-2 font-bold text-sm">
            <span>✅</span> Issue Report Received!
        </div>
        <p class="text-xs">
            Reference Number: <strong class="font-mono text-emerald-700 dark:text-emerald-300 text-sm font-black" x-text="submittedCode"></strong>.
            The technical diagnostic context has been captured.
        </p>
        <div class="pt-2 flex flex-wrap items-center gap-3">
            <button type="button" 
                    @click="trackSubmittedTicket()" 
                    class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                <span>🔍</span>
                <span>Track This Ticket Now</span>
            </button>
            <button type="button" @click="resetForm()" class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 underline">
                Report another issue
            </button>
        </div>
    </div>

    <form x-show="!submittedCode" @submit.prevent="submitReport()" class="space-y-4">
        <!-- Title -->
        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Issue Title <span class="text-rose-500">*</span></label>
            <input type="text"
                   x-model="form.title"
                   required
                   placeholder="e.g., Calculation units did not update, or PDF download failed"
                   class="w-full text-xs font-medium px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
        </div>

        <!-- Category & Severity Grid -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                <select x-model="form.category" class="w-full text-xs font-medium px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="calculation">⚡ Calculation / Units</option>
                    <option value="bill_download">📑 Bill Download / PDF</option>
                    <option value="mru_sync">🗂️ MRU / Cycles</option>
                    <option value="ui_display">🖥️ UI / Display Error</option>
                    <option value="wallet_payment">👛 Wallet / Billing</option>
                    <option value="other">❓ Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Severity</label>
                <select x-model="form.severity" class="w-full text-xs font-medium px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="low">🟢 Low (Cosmetic / Small)</option>
                    <option value="medium" selected>🟡 Medium (Normal)</option>
                    <option value="high">🟠 High (Blocks Workflow)</option>
                    <option value="critical">🔴 Critical (Data Error / Crash)</option>
                </select>
            </div>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description / What Happened? <span class="text-rose-500">*</span></label>
            <textarea x-model="form.description"
                      rows="3"
                      required
                      placeholder="Describe what you clicked, what you expected, and what actually happened..."
                      class="w-full text-xs font-medium px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>

        <!-- Auto-Captured Context Accordion -->
        <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 p-3 space-y-1.5 text-xs">
            <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5">
                    <span>🤖</span> Auto-Captured Diagnostic Context
                </span>
                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-mono">Ready</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-600 dark:text-slate-400 font-mono">
                <div>URL: <span class="font-semibold text-slate-800 dark:text-slate-200 truncate inline-block max-w-[160px]" x-text="window.location.pathname"></span></div>
                <div x-show="form.ca_number">CA: <span class="font-bold text-blue-600 dark:text-cyan-400" x-text="form.ca_number"></span></div>
                <div x-show="form.mru_id">MRU ID: <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="form.mru_id"></span></div>
                <div x-show="form.billing_month">Period: <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="form.billing_month + '/' + form.billing_year"></span></div>
            </div>
        </div>

        <!-- Error Alert -->
        <div x-show="errorMessage" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 text-xs font-semibold" x-text="errorMessage"></div>

        <!-- Modal Actions -->
        <div class="pt-2 flex items-center justify-end gap-3">
            <button type="button" @click="closeModal()" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                Cancel
            </button>
            <button type="submit"
                    :disabled="isSubmitting"
                    class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white shadow-md shadow-indigo-600/20 transition active:scale-95 disabled:opacity-60 cursor-pointer">
                <span x-show="isSubmitting" class="w-3 h-3 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                <span x-text="isSubmitting ? 'Sending...' : 'Submit Report 🚀'"></span>
            </button>
        </div>
    </form>
</div>
