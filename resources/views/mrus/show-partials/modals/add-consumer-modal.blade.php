<!-- MODAL: Add Single Consumer -->
<div x-show="showAddConsumerModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="showAddConsumerModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                    +
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Add Consumer</h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Register CA in MRU {{ $mru->code }}</p>
                </div>
            </div>
            <button @click="showAddConsumerModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
        </div>

        <form method="POST" action="{{ route('mrus.consumers.store', $mru) }}" class="overflow-y-auto p-4 sm:p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">CA Number *</label>
                <input type="text" name="ca_number" placeholder="e.g. 10230046961" required class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Consumer Name</label>
                <input type="text" name="consumer_name" placeholder="Full name" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Tariff</label>
                    <input type="text" name="tariff_category" placeholder="e.g. DS-II" value="DS-II" list="tariffOptions" class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                    <datalist id="tariffOptions">
                        <option value="DS-II">
                        <option value="DS-I">
                        <option value="NDS-II">
                        <option value="LTIS">
                        <option value="IAS">
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Basis</label>
                    <select name="billing_basis" class="w-full text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2.5 focus:ring-2 focus:ring-blue-500">
                        <option value="OK" selected>OK (Normal)</option>
                        <option value="LK">LK (Lock)</option>
                        <option value="MD">MD (Defective)</option>
                        <option value="PL">PL (Loss)</option>
                        <option value="RN">RN (No Reading)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Amount (₹)</label>
                    <input type="number" step="0.01" min="0" name="baseline_amount" placeholder="0.00" class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Meter Number</label>
                    <input type="text" name="meter_no" placeholder="e.g. 3808220" class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Initial / Prev Reading</label>
                    <input type="number" step="1" min="0" name="baseline_previous_reading" placeholder="e.g. 1000" class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Mobile</label>
                    <input type="text" name="mobile" placeholder="10 digits" class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Address (Optional)</label>
                <input type="text" name="address" placeholder="Village / Area details" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showAddConsumerModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center">Cancel</button>
                <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition text-center">Save Consumer</button>
            </div>
        </form>
    </div>
</div>
