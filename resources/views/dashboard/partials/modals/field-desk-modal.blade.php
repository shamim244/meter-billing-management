            <!-- MODAL 8: FieldDesk Quick Bridge Modal (Tier 2 Fast Pop-up) -->
            <div x-show="showFieldDeskModal" x-cloak
                 @keydown.escape.window="showFieldDeskModal = false"
                 class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="showFieldDeskModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    
                    <!-- Header -->
                    <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-cyan-500 text-white flex items-center justify-center font-bold text-base shadow-md shadow-emerald-500/20">
                                📋
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <span>FieldDesk Quick Bridge</span>
                                    <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">Tier 2</span>
                                </h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 font-mono" x-text="'CA: ' + (fieldDeskBill?.ca_number || '—') + (fieldDeskBill?.consumer_name ? ' • ' + fieldDeskBill.consumer_name : '')"></p>
                            </div>
                        </div>
                        <button type="button" @click="showFieldDeskModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

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

                    <!-- Body -->
                    <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        
                        <!-- Loading State -->
                        <div x-show="fieldDeskLoading" class="py-8 flex flex-col items-center justify-center text-slate-400 space-y-2">
                            <svg class="w-6 h-6 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span class="text-xs">Fetching FieldDesk records...</span>
                        </div>

                        <!-- Content when loaded -->
                        <div x-show="!fieldDeskLoading" class="space-y-4">
                            
                            <!-- CASE A: Active Action Exists -->
                            <template x-if="fieldDeskAction">
                                <div class="space-y-3.5">
                                    
                                    <!-- Status & Category Banner -->
                                    <div class="p-3 rounded-2xl border"
                                         :style="'background-color: ' + (fieldDeskAction.category_color || '#10b981') + '10; border-color: ' + (fieldDeskAction.category_color || '#10b981') + '30;'">
                                        <div class="flex items-center justify-between">
                                            <span class="inline-flex items-center gap-1.5 font-bold text-xs" :style="'color: ' + (fieldDeskAction.category_color || '#10b981')">
                                                <span x-text="fieldDeskAction.category_icon || '📋'"></span>
                                                <span x-text="fieldDeskAction.category_name"></span>
                                            </span>
                                            <div>
                                                <template x-if="fieldDeskAction.is_due_today">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-700 dark:text-rose-300 animate-pulse">
                                                        🚨 Due Today (<span x-text="fieldDeskAction.target_date_formatted"></span>)
                                                    </span>
                                                </template>
                                                <template x-if="fieldDeskAction.is_overdue">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300">
                                                        ⚠️ Overdue (<span x-text="fieldDeskAction.target_date_formatted"></span>)
                                                    </span>
                                                </template>
                                                <template x-if="fieldDeskAction.is_upcoming">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-700 dark:text-blue-300">
                                                        📅 Target: <span x-text="fieldDeskAction.target_date_formatted"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Financial Commitment if set -->
                                        <template x-if="fieldDeskAction.target_amount > 0">
                                            <div class="mt-2.5 pt-2 border-t border-slate-200/40 dark:border-slate-700/40 flex items-center justify-between text-xs">
                                                <span class="text-slate-500 dark:text-slate-400">Promised Amount:</span>
                                                <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 text-sm">
                                                    ₹<span x-text="Number(fieldDeskAction.target_amount).toLocaleString('en-IN')"></span>
                                                    <template x-if="fieldDeskBill?.total_amount">
                                                        <span class="text-[10px] text-slate-400 font-normal"> / Bill: ₹<span x-text="Math.round(fieldDeskBill.total_amount)"></span></span>
                                                    </template>
                                                </span>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Private Note Box -->
                                    <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-xs">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">📝 Dossier / Field Note:</span>
                                        <p class="text-slate-800 dark:text-slate-200 leading-relaxed font-sans" x-text="fieldDeskAction.private_note || 'No notes entered.'"></p>
                                    </div>

                                    <!-- Fast 2-Tap Action Buttons -->
                                    <div class="flex flex-wrap items-center gap-2 pt-1">
                                        <template x-if="fieldDeskAction.whatsapp_link">
                                            <a :href="fieldDeskAction.whatsapp_link" target="_blank"
                                               @click="logFieldDeskActivity(fieldDeskAction.id, 'whatsapp_sent', 'WhatsApp reminder initiated from Billing Dashboard')"
                                               class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95">
                                                <span>💬</span> WhatsApp
                                            </a>
                                        </template>
                                        <template x-if="fieldDeskAction.clean_mobile">
                                            <a :href="'tel:' + fieldDeskAction.clean_mobile"
                                               @click="logFieldDeskActivity(fieldDeskAction.id, 'call_made', 'Phone call placed from Billing Dashboard')"
                                               class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95">
                                                <span>📞</span> Call
                                            </a>
                                        </template>
                                        <button type="button" @click="quickSnoozeFromModal(fieldDeskAction.id, 2)"
                                                class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition active:scale-95">
                                            🔄 +2 Days
                                        </button>
                                        <button type="button" @click="quickSnoozeFromModal(fieldDeskAction.id, 5)"
                                                class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition active:scale-95">
                                            🔄 +5 Days
                                        </button>
                                        <button type="button" @click="completeActionFromModal(fieldDeskAction.id)"
                                                class="inline-flex items-center gap-1 px-3 py-2 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 rounded-xl text-xs font-bold border border-emerald-200 dark:border-emerald-800 transition active:scale-95">
                                            <span>✅</span> Done
                                        </button>
                                    </div>

                                </div>
                            </template>

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

                        </div>

                    </div>

                    <!-- Footer Bridge: Jump to Dedicated FieldDesk Workspace -->
                    <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <a :href="'/field-desk?ca=' + (fieldDeskBill?.ca_number || '')"
                           class="inline-flex items-center gap-1.5 font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                            <span>↗ Open in FieldDesk Hub</span>
                        </a>
                        <button type="button" @click="showFieldDeskModal = false" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 font-bold transition">
                            Close
                        </button>
                    </div>

                </div>
            </div>
