            <!-- Filter & Search Toolbar -->
            <div class="bg-white dark:bg-slate-900/90 backdrop-blur-md rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                
                <!-- Timeline Filter Tabs -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
                    <button @click="setTimeline('all_active')"
                            :class="timeline === 'all_active' ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                            class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition">
                        ⚡ All Active
                    </button>
                    <button @click="setTimeline('today')"
                            :class="timeline === 'today' ? 'bg-rose-600 text-white font-bold shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                            class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition flex items-center gap-1">
                        <span>🚨</span> Due Today
                        <span x-show="counts.due_today > 0" class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-rose-500/20 text-rose-200" x-text="counts.due_today"></span>
                    </button>
                    <button @click="setTimeline('overdue')"
                            :class="timeline === 'overdue' ? 'bg-amber-600 text-white font-bold shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                            class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition flex items-center gap-1">
                        <span>⚠️</span> Overdue
                        <span x-show="counts.overdue > 0" class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-amber-500/20 text-amber-200" x-text="counts.overdue"></span>
                    </button>
                    <button @click="setTimeline('upcoming')"
                            :class="timeline === 'upcoming' ? 'bg-blue-600 text-white font-bold shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                            class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition flex items-center gap-1">
                        <span>📅</span> Upcoming
                    </button>
                    <button @click="setTimeline('resolved')"
                            :class="timeline === 'resolved' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                            class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition flex items-center gap-1">
                        <span>✅</span> Resolved
                    </button>
                    <button @click="setTimeline('all')"
                            :class="timeline === 'all' ? 'bg-slate-700 text-white font-bold shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                            class="px-3.5 py-1.5 rounded-xl text-xs whitespace-nowrap transition">
                        📂 All Archive
                    </button>
                </div>

                <!-- Secondary Filters: Search, Category, MRU, Priority -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                    
                    <!-- Search Input -->
                    <div class="lg:col-span-5 relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-sm">
                            🔍
                        </span>
                        <input type="text"
                               x-model="search"
                               @input.debounce.350ms="fetchData()"
                               placeholder="Search CA number, consumer name, mobile, notes..."
                               class="w-full pl-9 pr-8 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <button x-show="search" @click="search = ''; fetchData()" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 text-xs">
                            ✕
                        </button>
                    </div>

                    <!-- Category Picker -->
                    <div class="lg:col-span-3">
                        <select x-model="categoryId" @change="fetchData()" class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">All Action Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- MRU Picker -->
                    <div class="lg:col-span-2">
                        <select x-model="mruId" @change="fetchData()" class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">All MRUs</option>
                            @foreach($mrus as $m)
                                <option value="{{ $m->id }}">{{ $m->code }} - {{ Str::limit($m->name, 15) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Priority Picker -->
                    <div class="lg:col-span-2">
                        <select x-model="priority" @change="fetchData()" class="w-full py-2 px-3 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">All Priorities</option>
                            <option value="urgent">🚨 Urgent</option>
                            <option value="high">⚠️ High</option>
                            <option value="normal">🔵 Normal</option>
                            <option value="low">⚪ Low</option>
                        </select>
                    </div>

                </div>

            </div>