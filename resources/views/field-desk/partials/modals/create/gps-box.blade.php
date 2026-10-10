<!-- High-Precision Zero-API GPS Coordinates Box -->
<div class="p-3.5 bg-slate-50 dark:bg-slate-800/80 rounded-2xl border border-slate-200 dark:border-slate-700/80 space-y-2.5">
    <div class="flex items-center justify-between">
        <div>
            <span class="text-xs font-black text-slate-800 dark:text-white flex items-center gap-1.5">
                <span>📍</span> Consumer GPS Coordinates
            </span>
            <span class="text-[10px] text-slate-500 dark:text-slate-400">Zero-API satellite geolocation (high precision target &lt; 10m)</span>
        </div>
        <button type="button"
                @click="captureGpsLocation('create')"
                :disabled="gpsLoading"
                class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-sm disabled:opacity-50">
            <template x-if="gpsLoading">
                <span class="inline-flex items-center gap-1">
                    <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>Locking GPS...</span>
                </span>
            </template>
            <template x-if="!gpsLoading">
                <span>📍 Capture My GPS</span>
            </template>
        </button>
    </div>

    <!-- Accuracy & Status Notification -->
    <template x-if="form.location_accuracy">
        <div class="flex items-center justify-between text-xs p-2 rounded-xl border"
             :class="form.location_accuracy <= 10 ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 text-emerald-700 dark:text-emerald-300' : (form.location_accuracy <= 25 ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 text-amber-700 dark:text-amber-300' : 'bg-orange-50 dark:bg-orange-950/40 border-orange-200 text-orange-700 dark:text-orange-300')">
            <span class="font-bold flex items-center gap-1">
                <span x-text="form.location_accuracy <= 10 ? '🟢 High Precision' : (form.location_accuracy <= 25 ? '🟡 Good Accuracy' : '🟠 Moderate Accuracy')"></span>
                <span class="font-mono text-[11px]">(±<span x-text="Math.round(form.location_accuracy)"></span>m radius)</span>
            </span>
            <template x-if="form.latitude && form.longitude">
                <a :href="'https://www.google.com/maps?q=' + form.latitude + ',' + form.longitude"
                   target="_blank"
                   class="font-bold underline text-blue-600 dark:text-cyan-400">
                    🗺️ Preview in Maps
                </a>
            </template>
        </div>
    </template>

    <!-- Lat/Lng Grid -->
    <div class="grid grid-cols-2 gap-2 text-xs">
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase">Latitude</label>
            <input type="number" step="0.00000001" x-model="form.latitude" placeholder="e.g. 26.123456"
                   class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 font-mono text-xs">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase">Longitude</label>
            <input type="number" step="0.00000001" x-model="form.longitude" placeholder="e.g. 85.123456"
                   class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 font-mono text-xs">
        </div>
    </div>

    <!-- Toggle to store permanently with Consumer Account -->
    <div class="flex items-center justify-between pt-1">
        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
            <input type="checkbox" x-model="form.save_to_consumer" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            <span>Save coordinates permanently to Consumer Account</span>
        </label>
        <button type="button" x-show="form.latitude || form.longitude"
                @click="form.latitude = ''; form.longitude = ''; form.location_accuracy = null"
                class="text-[10px] text-slate-400 hover:text-rose-500">
            ✕ Clear
        </button>
    </div>
</div>
