<!-- Consumer Contact & GPS Quick Bar -->
<div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-xs shrink-0">
    <!-- Consumer Mobile -->
    <div class="flex items-center gap-1.5">
        <span class="text-slate-400 font-bold">📱 Mobile:</span>
        <template x-if="fieldDeskBill?.mobile || fieldDeskAction?.consumer_mobile">
            <span class="inline-flex items-center gap-1 font-mono font-bold text-slate-800 dark:text-slate-200">
                <span x-text="fieldDeskBill?.mobile || fieldDeskAction?.consumer_mobile"></span>
                <a :href="'tel:' + (fieldDeskBill?.mobile || fieldDeskAction?.consumer_mobile)" class="px-1 text-[11px] text-blue-600 dark:text-cyan-400 hover:underline" title="Call">📞</a>
                <a :href="'https://wa.me/91' + (fieldDeskBill?.mobile || fieldDeskAction?.consumer_mobile)" target="_blank" class="px-1 text-[11px] text-emerald-600 hover:underline" title="WhatsApp">💬</a>
            </span>
        </template>
        <template x-if="!fieldDeskBill?.mobile && !fieldDeskAction?.consumer_mobile">
            <span class="text-slate-400 italic">None</span>
        </template>
    </div>

    <!-- Consumer GPS Coordinates -->
    <div class="flex items-center gap-1.5">
        <span class="text-slate-400 font-bold">📍 GPS:</span>
        <template x-if="(fieldDeskBill?.latitude && fieldDeskBill?.longitude) || (fieldDeskAction?.latitude && fieldDeskAction?.longitude)">
            <span class="inline-flex items-center gap-1">
                <a :href="'https://www.google.com/maps?q=' + (fieldDeskBill?.latitude || fieldDeskAction?.latitude) + ',' + (fieldDeskBill?.longitude || fieldDeskAction?.longitude)"
                   target="_blank"
                   class="font-mono text-[11px] text-blue-600 dark:text-cyan-400 underline font-bold">
                    🗺️ Open Map
                </a>
                <template x-if="fieldDeskBill?.location_accuracy || fieldDeskAction?.location_accuracy">
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-mono">
                        (±<span x-text="Math.round(fieldDeskBill?.location_accuracy || fieldDeskAction?.location_accuracy)"></span>m)
                    </span>
                </template>
            </span>
        </template>
        <template x-if="!((fieldDeskBill?.latitude && fieldDeskBill?.longitude) || (fieldDeskAction?.latitude && fieldDeskAction?.longitude))">
            <span class="text-slate-400 italic">Not Tagged</span>
        </template>
        <!-- 1-Click On-the-spot GPS Lock Button -->
        <button type="button"
                @click="captureFieldDeskGps(true)"
                :disabled="fieldDeskGpsLoading"
                class="ml-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-900/40 cursor-pointer disabled:opacity-50">
            <span x-show="!fieldDeskGpsLoading">📍 Tag GPS</span>
            <span x-show="fieldDeskGpsLoading">🛰️ Locking...</span>
        </button>
    </div>
</div>
