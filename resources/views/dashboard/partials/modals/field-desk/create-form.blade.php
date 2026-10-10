<!-- CASE B: No Active Action Exists -> 2-Tap Quick Creation -->
<template x-if="!fieldDeskAction">
    <div class="space-y-3 text-xs">
        <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs">
            No active FieldDesk commitment for this consumer yet. Add a quick follow-up action below:
        </div>

        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
            <select x-model="quickDeskForm.category_id" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                @if(isset($fieldDeskCategories) && count($fieldDeskCategories) > 0)
                    @foreach($fieldDeskCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                    @endforeach
                @else
                    <option value="1">💳 Payment Commitment</option>
                    <option value="2">🔧 Technical / Grievance</option>
                    <option value="3">🚶 Scheduled Visit</option>
                    <option value="4">📝 Field Dossier Note</option>
                @endif
            </select>
        </div>

        <!-- Consumer Mobile (Optional override) -->
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Consumer Mobile (Optional)</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs font-mono">📱 +91</span>
                <input type="tel" x-model="quickDeskForm.mobile" maxlength="10" placeholder="9876543210" class="w-full pl-14 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="font-bold text-slate-700 dark:text-slate-300">Target Date</label>
                <div class="flex items-center gap-1">
                    <button type="button" @click="setQuickDeskPreset(0)" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Today</button>
                    <button type="button" @click="setQuickDeskPreset(2)" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">+2d</button>
                    <button type="button" @click="setQuickDeskPreset(5)" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">+5d</button>
                </div>
            </div>
            <input type="date" x-model="quickDeskForm.target_date" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
        </div>

        <div class="grid grid-cols-2 gap-2.5">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Amount (₹)</label>
                <input type="number" step="0.01" x-model="quickDeskForm.target_amount" placeholder="0.00" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
            </div>
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Priority</label>
                <select x-model="quickDeskForm.priority" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    <option value="normal">Normal</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Private Note</label>
            <input type="text" x-model="quickDeskForm.private_note" placeholder="e.g. PhonePe: 9876543210 / Visit Sunday after 6pm" class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
        </div>

        <!-- Zero-API Hardware GPS Coordinates Box -->
        <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-2">
            <div class="flex items-center justify-between">
                <label class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1 text-xs">
                    <span>📍</span> GPS Coordinates (Sub-10m Satellite Lock)
                </label>
                <button type="button"
                        @click="captureFieldDeskGps(false)"
                        :disabled="fieldDeskGpsLoading"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs disabled:opacity-50 flex items-center gap-1 cursor-pointer">
                    <span x-show="!fieldDeskGpsLoading">📍 Capture GPS</span>
                    <span x-show="fieldDeskGpsLoading">🛰️ Locking...</span>
                </button>
            </div>
            <template x-if="quickDeskForm.location_accuracy">
                <div class="text-[11px] flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-bold">
                    <span>🟢 Accuracy: ±<span x-text="Math.round(quickDeskForm.location_accuracy)"></span>m</span>
                    <a :href="'https://www.google.com/maps?q=' + quickDeskForm.latitude + ',' + quickDeskForm.longitude" target="_blank" class="underline text-blue-600 dark:text-cyan-400">🗺️ Preview Map</a>
                </div>
            </template>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <input type="number" step="0.00000001" x-model="quickDeskForm.latitude" placeholder="Latitude" class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 font-mono text-xs">
                <input type="number" step="0.00000001" x-model="quickDeskForm.longitude" placeholder="Longitude" class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 font-mono text-xs">
            </div>
            <label class="flex items-center gap-2 cursor-pointer text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                <input type="checkbox" x-model="quickDeskForm.save_to_consumer" class="rounded border-slate-300 text-emerald-600">
                <span>Sync coordinates to Consumer Account permanently</span>
            </label>
        </div>

        <button type="button" @click="saveQuickDeskAction()" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md shadow-emerald-500/20 transition">
            💾 Save FieldDesk Action
        </button>
    </div>
</template>
