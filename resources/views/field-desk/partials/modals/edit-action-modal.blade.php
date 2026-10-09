        <!-- 4. EDIT ACTION MODAL -->
        <div x-show="modals.edit"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="modals.edit = false"
                 class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>✏️</span> Edit FieldDesk Action
                    </h3>
                    <button @click="modals.edit = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>

                <form @submit.prevent="submitEdit()" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Date *</label>
                        <input type="date"
                               x-model="editForm.target_date"
                               required
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                            <select x-model="editForm.category_id" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Priority</label>
                            <select x-model="editForm.priority" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Amount (₹)</label>
                            <input type="number"
                                   step="0.01"
                                   x-model="editForm.target_amount"
                                   class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Payment Mode</label>
                            <select x-model="editForm.payment_mode" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                <option value="">Select Mode...</option>
                                <option value="cash">Cash in hand</option>
                                <option value="upi_phonepe">UPI / PhonePe</option>
                                <option value="online">Online Portal</option>
                                <option value="office">Subdivision Office</option>
                            </select>
                        </div>
                    </div>

                    <!-- Mobile Input -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Consumer Mobile</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs font-mono">📱 +91</span>
                            <input type="tel"
                                   x-model="editForm.mobile"
                                   maxlength="10"
                                   placeholder="10-digit mobile number"
                                   class="w-full pl-14 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Private Field Note</label>
                        <textarea x-model="editForm.private_note"
                                  rows="2"
                                  class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
                    </div>

                    <!-- High-Precision Zero-API GPS Coordinates Box (Edit) -->
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-800/80 rounded-2xl border border-slate-200 dark:border-slate-700/80 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-black text-slate-800 dark:text-white flex items-center gap-1.5">
                                    <span>📍</span> Consumer GPS Coordinates
                                </span>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400">Zero-API satellite geolocation (high precision target &lt; 10m)</span>
                            </div>
                            <button type="button"
                                    @click="captureGpsLocation('edit')"
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
                        <template x-if="editForm.location_accuracy">
                            <div class="flex items-center justify-between text-xs p-2 rounded-xl border"
                                 :class="editForm.location_accuracy <= 10 ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 text-emerald-700 dark:text-emerald-300' : (editForm.location_accuracy <= 25 ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 text-amber-700 dark:text-amber-300' : 'bg-orange-50 dark:bg-orange-950/40 border-orange-200 text-orange-700 dark:text-orange-300')">
                                <span class="font-bold flex items-center gap-1">
                                    <span x-text="editForm.location_accuracy <= 10 ? '🟢 High Precision' : (editForm.location_accuracy <= 25 ? '🟡 Good Accuracy' : '🟠 Moderate Accuracy')"></span>
                                    <span class="font-mono text-[11px]">(±<span x-text="Math.round(editForm.location_accuracy)"></span>m radius)</span>
                                </span>
                                <template x-if="editForm.latitude && editForm.longitude">
                                    <a :href="'https://www.google.com/maps?q=' + editForm.latitude + ',' + editForm.longitude"
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
                                <input type="number" step="0.00000001" x-model="editForm.latitude" placeholder="e.g. 26.123456"
                                       class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 font-mono text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase">Longitude</label>
                                <input type="number" step="0.00000001" x-model="editForm.longitude" placeholder="e.g. 85.123456"
                                       class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 font-mono text-xs">
                            </div>
                        </div>

                        <!-- Toggle to store permanently with Consumer Account -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
                                <input type="checkbox" x-model="editForm.save_to_consumer" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span>Sync coordinates to Consumer Account</span>
                            </label>
                            <button type="button" x-show="editForm.latitude || editForm.longitude"
                                    @click="editForm.latitude = ''; editForm.longitude = ''; editForm.location_accuracy = null"
                                    class="text-[10px] text-slate-400 hover:text-rose-500">
                                ✕ Clear
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modals.edit = false" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-md shadow-blue-500/20">
                            Save Changes
                        </button>
                    </div>
                </form>

            </div>
        </div>