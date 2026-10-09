            <!-- Feed Content Area -->
            <div>
                <!-- Loading Skeleton -->
                <div x-show="loading" class="py-12 flex flex-col items-center justify-center text-slate-400 space-y-3">
                    <svg class="animate-spin h-8 w-8 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span class="text-xs font-semibold">Loading FieldDesk Agenda...</span>
                </div>

                <!-- Empty State -->
                <div x-show="!loading && items.length === 0" class="bg-white dark:bg-slate-900/90 rounded-3xl p-10 text-center border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-3xl">
                        📋
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">
                        No FieldDesk Actions Found
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                        No consumer commitments match the current timeline or filter criteria. Create a new follow-up action to track payment promises or field visits.
                    </p>
                    <button @click="openCreateModal()" class="mt-2 inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
                        <span>➕</span> Create First Action
                    </button>
                </div>

                <!-- Agenda Cards List -->
                <div x-show="!loading && items.length > 0" class="space-y-3">
                    <template x-for="item in items" :key="item.id">
                        <div class="bg-white dark:bg-slate-900/95 rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition duration-150">
                            
                            <!-- Card Header: Category & Priority & Timing -->
                            <div class="flex flex-wrap items-center justify-between gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                                
                                <div class="flex items-center gap-2">
                                    <!-- Dynamic Category Pill -->
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold"
                                          :style="'background-color: ' + item.category_color + '15; color: ' + item.category_color + '; border: 1px solid ' + item.category_color + '30;'">
                                        <span x-text="item.category_icon"></span>
                                        <span x-text="item.category_name"></span>
                                    </span>

                                    <!-- Priority Badge -->
                                    <span x-show="item.priority === 'urgent'" class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400 border border-red-200 dark:border-red-800/60">
                                        🚨 Urgent
                                    </span>
                                    <span x-show="item.priority === 'high'" class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60">
                                        ⚠️ High
                                    </span>

                                    <!-- Reschedule Count Badge -->
                                    <span x-show="item.reschedule_count > 0" class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60">
                                        🔄 Snoozed <span x-text="item.reschedule_count"></span>x
                                    </span>
                                </div>

                                <!-- Due Timing Badge -->
                                <div>
                                    <template x-if="item.status === 'completed'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                            ✅ Resolved
                                        </span>
                                    </template>
                                    <template x-if="item.status !== 'completed' && item.is_due_today">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 animate-pulse">
                                            🚨 Due Today (<span x-text="item.target_date_formatted"></span>)
                                        </span>
                                    </template>
                                    <template x-if="item.status !== 'completed' && item.is_overdue">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                            ⚠️ Overdue by <span x-text="Math.abs(item.days_diff)"></span>d (<span x-text="item.target_date_formatted"></span>)
                                        </span>
                                    </template>
                                    <template x-if="item.status !== 'completed' && item.is_upcoming">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60">
                                            📅 Due in <span x-text="item.days_diff"></span>d (<span x-text="item.target_date_formatted"></span>)
                                        </span>
                                    </template>
                                </div>

                            </div>

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

                            <!-- Card Footer: Quick Actions Bar (1-2 tap velocity) -->
                            <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
                                
                                <!-- Communication & Snooze Shortcuts -->
                                <div class="flex flex-wrap items-center gap-1.5">
                                    
                                    <!-- 1-Click WhatsApp Trigger -->
                                    <template x-if="item.whatsapp_link">
                                        <a :href="item.whatsapp_link" target="_blank"
                                           @click="logCommunication(item.id, 'whatsapp_sent', 'WhatsApp reminder initiated')"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95"
                                           title="Send pre-filled Hindi WhatsApp template">
                                            <span>💬</span> WhatsApp
                                        </a>
                                    </template>

                                    <!-- 1-Click Phone Call Trigger -->
                                    <template x-if="item.mobile">
                                        <a :href="'tel:' + item.mobile"
                                           @click="logCommunication(item.id, 'call_made', 'Phone call placed')"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95"
                                           title="Call consumer directly">
                                            <span>📞</span> Call
                                        </a>
                                    </template>

                                    <!-- 1-Click GPS Navigation -->
                                    <template x-if="item.map_link">
                                        <a :href="item.map_link" target="_blank"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95"
                                           title="Open GPS navigation in Google Maps">
                                            <span>📍</span> Map
                                        </a>
                                    </template>

                                    <!-- +2 Days Quick Snooze -->
                                    <template x-if="item.status !== 'completed'">
                                        <button @click="quickReschedule(item.id, 2)"
                                                class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition active:scale-95"
                                                title="Snooze target date by 2 days">
                                            +2d
                                        </button>
                                    </template>

                                    <!-- +5 Days Quick Snooze -->
                                    <template x-if="item.status !== 'completed'">
                                        <button @click="quickReschedule(item.id, 5)"
                                                class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition active:scale-95"
                                                title="Snooze target date by 5 days">
                                            +5d
                                        </button>
                                    </template>

                                    <!-- +7 Days Quick Snooze -->
                                    <template x-if="item.status !== 'completed'">
                                        <button @click="quickReschedule(item.id, 7)"
                                                class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition active:scale-95"
                                                title="Snooze target date by 7 days">
                                            +7d
                                        </button>
                                    </template>

                                    <!-- Mark Done Button -->
                                    <template x-if="item.status !== 'completed'">
                                        <button @click="openCompleteModal(item)"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 rounded-xl text-xs font-bold border border-emerald-200 dark:border-emerald-800 transition active:scale-95">
                                            <span>✅</span> Mark Done
                                        </button>
                                    </template>
                                </div>

                                <!-- Utility & History Controls -->
                                <div class="flex items-center gap-2">
                                    <!-- View Timeline Drawer -->
                                    <button @click="openTimelineDrawer(item.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="View complete interaction touch timeline">
                                        <span>🕒</span> History
                                    </button>

                                    <!-- Edit Action -->
                                    <button @click="openEditModal(item)"
                                            class="p-1.5 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="Edit details">
                                        ✏️
                                    </button>

                                    <!-- Delete / Cancel Action -->
                                    <button @click="deleteAction(item.id)"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                            title="Delete action">
                                        🗑️
                                    </button>
                                </div>

                            </div>

                        </div>
                    </template>
                </div>

                <!-- Pagination Bar -->
                <div x-show="!loading && pagination.total > pagination.per_page" class="flex items-center justify-between pt-4 text-xs text-slate-500">
                    <div>
                        Showing <span class="font-bold text-slate-800 dark:text-slate-200" x-text="((pagination.current_page - 1) * pagination.per_page) + 1"></span>
                        to <span class="font-bold text-slate-800 dark:text-slate-200" x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span>
                        of <span class="font-bold text-slate-800 dark:text-slate-200" x-text="pagination.total"></span> actions
                    </div>
                    <div class="flex items-center gap-2">
                        <button :disabled="pagination.current_page <= 1"
                                @click="goToPage(pagination.current_page - 1)"
                                :class="pagination.current_page <= 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-200 dark:hover:bg-slate-700'"
                                class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold transition">
                            Previous
                        </button>
                        <button :disabled="pagination.current_page >= pagination.last_page"
                                @click="goToPage(pagination.current_page + 1)"
                                :class="pagination.current_page >= pagination.last_page ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-200 dark:hover:bg-slate-700'"
                                class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold transition">
                            Next
                        </button>
                    </div>
                </div>

            </div>