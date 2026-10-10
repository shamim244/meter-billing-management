<div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-xl">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <span>⏳</span> 3. Duration Pricing Table (Day-Wise & Month-Wise)
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Customize duration tiers, discounts, overrides, or add new day/month tiers.</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="addDuration('day', 7)" class="px-3 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-lg text-xs font-bold transition flex items-center gap-1">
                <span>⏱️</span> + Add Days
            </button>
            <button type="button" @click="addDuration('month', 1)" class="px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded-lg text-xs font-bold transition flex items-center gap-1">
                <span>📅</span> + Add Months
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                    <th class="pb-2 pr-2">Unit</th>
                    <th class="pb-2 pr-2">Duration</th>
                    <th class="pb-2 pr-2">Label (Optional)</th>
                    <th class="pb-2 pr-2">Discount %</th>
                    <th class="pb-2 pr-2">Final Price (₹)</th>
                    <th class="pb-2 pr-2">Extra MRU Rate</th>
                    <th class="pb-2 pr-2">Extra CA Rate</th>
                    <th class="pb-2 text-right">Remove</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                <template x-for="(d, index) in durations" :key="index">
                    <tr>
                        <!-- Hidden ID if existing -->
                        <input type="hidden" :name="'durations[' + index + '][id]'" :value="d.id">

                        <!-- Unit Select -->
                        <td class="py-2.5 pr-2">
                            <select :name="'durations[' + index + '][duration_unit]'" x-model="d.unit" @change="recalculateDurations()" class="text-xs bg-slate-950 border-slate-800 rounded-lg text-white p-1.5 focus:ring-indigo-500 font-bold">
                                <option value="month">📅 Month</option>
                                <option value="day">⏱️ Day</option>
                            </select>
                        </td>

                        <!-- Duration Value -->
                        <td class="py-2.5 pr-2">
                            <div class="flex items-center gap-1">
                                <input type="number" min="1" max="3650" :name="'durations[' + index + '][duration_value]'" x-model.number="d.value" @input="recalculateDurations()" required class="w-16 text-xs bg-slate-950 border-slate-800 rounded-lg text-white p-1.5 focus:ring-indigo-500 font-mono font-bold">
                                <span class="text-[10px] text-slate-400" x-text="d.unit === 'day' ? 'Days' : 'Mo'"></span>
                            </div>
                        </td>

                        <!-- Name / Label -->
                        <td class="py-2.5 pr-2">
                            <input type="text" :name="'durations[' + index + '][name]'" x-model="d.name" placeholder="e.g. Trial" class="w-24 text-xs bg-slate-950 border-slate-800 rounded-lg text-white p-1.5 focus:ring-indigo-500">
                        </td>

                        <!-- Discount % -->
                        <td class="py-2.5 pr-2">
                            <input type="number" step="0.1" min="0" max="100" :name="'durations[' + index + '][discount_percent]'" x-model.number="d.discount" @input="recalculateDurations()" class="w-16 text-xs bg-slate-950 border-slate-800 rounded-lg text-white p-1.5 focus:ring-indigo-500 font-mono">
                        </td>

                        <!-- Final Price -->
                        <td class="py-2.5 pr-2">
                            <input type="number" step="0.01" min="0" :name="'durations[' + index + '][final_price]'" x-model.number="d.price" required class="w-24 text-xs bg-slate-950 border-slate-800 rounded-lg text-emerald-400 font-bold p-1.5 focus:ring-indigo-500 font-mono">
                        </td>

                        <!-- Extra MRU Rate -->
                        <td class="py-2.5 pr-2">
                            <input type="number" step="0.01" min="0" :name="'durations[' + index + '][extra_mru_rate]'" x-model="d.extraMru" placeholder="Base" class="w-20 text-xs bg-slate-950 border-slate-800 rounded-lg text-white p-1.5 focus:ring-indigo-500 font-mono">
                        </td>

                        <!-- Extra CA Rate -->
                        <td class="py-2.5 pr-2">
                            <input type="number" step="0.01" min="0" :name="'durations[' + index + '][extra_consumer_rate]'" x-model="d.extraConsumer" placeholder="Base" class="w-20 text-xs bg-slate-950 border-slate-800 rounded-lg text-white p-1.5 focus:ring-indigo-500 font-mono">
                        </td>

                        <!-- Delete Button -->
                        <td class="py-2.5 text-right">
                            <button type="button" @click="removeDuration(index)" :disabled="durations.length <= 1" class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 disabled:opacity-30 text-rose-400 rounded-lg transition" title="Remove Duration">
                                🗑️
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
