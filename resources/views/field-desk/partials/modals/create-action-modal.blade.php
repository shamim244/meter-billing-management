        <!-- 1. CREATE / NEW ACTION MODAL -->
        <div x-show="modals.create"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="modals.create = false"
                 class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>➕</span> New FieldDesk Action
                    </h3>
                    <button @click="modals.create = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>

                <form @submit.prevent="submitCreate()" class="space-y-3.5 text-xs">
                    
                    <!-- CA Number & Mobile Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Consumer CA Number *</label>
                            <input type="text"
                                   x-model="form.ca_number"
                                   @change="if(form.ca_number) openCreateModal(form.ca_number)"
                                   required
                                   placeholder="e.g. 10230041576"
                                   class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Consumer Mobile (Optional)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs font-mono">📱 +91</span>
                                <input type="tel"
                                       x-model="form.mobile"
                                       maxlength="10"
                                       placeholder="10-digit mobile"
                                       class="w-full pl-14 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-emerald-500">
                            </div>
                        </div>
                    </div>

                    <!-- Category Picker -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Action Category *</label>
                        <select x-model="form.category_id" required class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Target Date & Quick Date Presets -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="font-bold text-slate-700 dark:text-slate-300">Target Date *</label>
                            <!-- Quick Presets -->
                            <div class="flex items-center gap-1">
                                <button type="button" @click="setDatePreset(0)" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Today</button>
                                <button type="button" @click="setDatePreset(1)" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">+1d</button>
                                <button type="button" @click="setDatePreset(2)" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">+2d</button>
                                <button type="button" @click="setDatePreset(5)" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">+5d</button>
                            </div>
                        </div>
                        <input type="date"
                               x-model="form.target_date"
                               required
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                    </div>

                    <!-- Priority & MRU Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Priority</label>
                            <select x-model="form.priority" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">MRU (Optional)</label>
                            <select x-model="form.mru_id" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                <option value="">Auto-detect / None</option>
                                @foreach($mrus as $m)
                                    <option value="{{ $m->id }}">{{ $m->code }} - {{ Str::limit($m->name, 12) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Promised Amount & Payment Mode -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Amount (₹)</label>
                            <input type="number"
                                   step="0.01"
                                   x-model="form.target_amount"
                                   placeholder="0.00"
                                   class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Expected Mode</label>
                            <select x-model="form.payment_mode" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                <option value="">Select Mode...</option>
                                <option value="cash">Cash in hand</option>
                                <option value="upi_phonepe">UPI / PhonePe</option>
                                <option value="online">Online Portal</option>
                                <option value="office">Subdivision Office</option>
                            </select>
                        </div>
                    </div>

                    <!-- Private Note / Dossier Details -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Field Dossier Note</label>
                        <textarea x-model="form.private_note"
                                  rows="2"
                                  placeholder="e.g. PhonePe: 9876543210 • Salary on 10th • 2nd house near temple"
                                  class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
                    </div>

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

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modals.create = false" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md shadow-emerald-500/20">
                            Create Action
                        </button>
                    </div>

                </form>

            </div>
        </div>