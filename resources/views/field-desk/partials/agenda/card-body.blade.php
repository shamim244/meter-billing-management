<!-- Card Body: Consumer, Amount & Notes -->
<div class="grid grid-cols-1 md:grid-cols-12 gap-3 py-3">
    <!-- Consumer Identifiers -->
    <div class="md:col-span-5 space-y-1">
        <div class="flex items-center gap-2">
            <span class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white" x-text="item.consumer_name"></span>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <span class="font-mono font-bold text-slate-700 dark:text-slate-300 cursor-pointer hover:underline"
                  @click="copyText(item.ca_number, 'CA Number copied!')"
                  title="Click to copy CA">
                CA: <span x-text="item.ca_number"></span> 📋
            </span>
            <template x-if="item.mru_code">
                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-bold">
                    MRU: <span x-text="item.mru_code"></span>
                </span>
            </template>
            <template x-if="item.mobile">
                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono cursor-pointer hover:underline flex items-center gap-1"
                      @click="copyText(item.mobile, 'Mobile copied!')"
                      title="Click to copy mobile">
                    📱 <span x-text="item.mobile"></span>
                </span>
            </template>
            <template x-if="!item.mobile">
                <button type="button" @click="openQuickMobileModal(item)"
                        class="px-2 py-0.5 rounded bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 text-[10px] font-bold transition flex items-center gap-1"
                        title="Add mobile number for this consumer">
                    <span>📱+</span> Add Mobile
                </button>
            </template>

            <!-- GPS Coordinates Tag -->
            <template x-if="item.latitude && item.longitude">
                <a :href="item.map_link" target="_blank"
                   class="px-2 py-0.5 rounded bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 text-[10px] font-mono hover:underline flex items-center gap-1"
                   :title="'Open GPS Location: ' + item.latitude + ', ' + item.longitude + (item.location_accuracy ? ' (±' + Math.round(item.location_accuracy) + 'm)' : '')">
                    <span>📍</span>
                    <span x-text="Number(item.latitude).toFixed(4) + ', ' + Number(item.longitude).toFixed(4)"></span>
                    <template x-if="item.location_accuracy">
                        <span class="text-[9px] text-rose-500 font-bold" x-text="'(±' + Math.round(item.location_accuracy) + 'm)'"></span>
                    </template>
                </a>
            </template>
            <template x-if="!item.latitude">
                <button type="button" @click="openQuickGpsModal(item)"
                        class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-slate-500 hover:text-rose-600 dark:hover:text-rose-300 border border-dashed border-slate-300 dark:border-slate-700 text-[10px] font-bold transition flex items-center gap-1"
                        title="Tag GPS satellite location for this consumer">
                    <span>📍+</span> Tag GPS
                </button>
            </template>
        </div>
    </div>

    <!-- Financial Commitment (if applicable) -->
    <div class="md:col-span-3">
        <template x-if="item.target_amount > 0">
            <div class="bg-emerald-50/60 dark:bg-emerald-950/30 p-2.5 rounded-xl border border-emerald-100 dark:border-emerald-800/40">
                <div class="text-[10px] uppercase font-bold text-emerald-700 dark:text-emerald-400">
                    Promised Amount
                </div>
                <div class="text-base sm:text-lg font-black text-emerald-800 dark:text-emerald-300 font-mono">
                    ₹<span x-text="Number(item.target_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                </div>
                <template x-if="item.collected_amount > 0">
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                        Paid: ₹<span x-text="Number(item.collected_amount).toLocaleString('en-IN')"></span>
                        <template x-if="item.remaining_amount > 0">
                            <span>(Rem: ₹<span x-text="Number(item.remaining_amount).toLocaleString('en-IN')"></span>)</span>
                        </template>
                    </div>
                </template>
                <template x-if="item.payment_mode">
                    <div class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 capitalize">
                        Mode: <span x-text="item.payment_mode"></span>
                    </div>
                </template>
            </div>
        </template>
        <template x-if="!item.target_amount || item.target_amount == 0">
            <div class="text-xs text-slate-400 italic">
                No financial amount bound
            </div>
        </template>
    </div>

    <!-- Private Dossier Note / PhonePe Details -->
    <div class="md:col-span-4">
        <template x-if="item.private_note">
            <div class="bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl border border-slate-200/60 dark:border-slate-700/60 text-xs text-slate-700 dark:text-slate-300">
                <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">📝 Field Note</span>
                <span class="line-clamp-2 leading-relaxed" x-text="item.private_note"></span>
            </div>
        </template>
        <template x-if="item.resolution_note">
            <div class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">
                Resolution: <span x-text="item.resolution_note"></span>
            </div>
        </template>
    </div>
</div>
