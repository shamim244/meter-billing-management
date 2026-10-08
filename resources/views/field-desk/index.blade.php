<x-app-layout>
    <div x-data="fieldDeskApp()" x-init="initApp()"
         @keydown.escape.window="modals.create = false; modals.complete = false; modals.timeline = false; modals.edit = false"
         class="py-6 sm:py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Hero Header & Stats Banner -->
            <div class="bg-white dark:bg-slate-900/90 backdrop-blur-md rounded-3xl p-5 sm:p-7 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 mb-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Module 11 • Ground Operations Engine
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                            <span>⚡</span> FieldDesk Command Center
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                            Zero-loss revenue commitments, meter dispute resolution targets, and scheduled village premise visits across your MRUs.
                        </p>
                    </div>

                    <!-- Top Action Buttons -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button @click="openCreateModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 hover:shadow-emerald-500/30 transition active:scale-[0.98]">
                            <span>➕</span> New Action Item
                        </button>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition active:scale-[0.98]">
                            <span>📊</span> Billing Dashboard
                        </a>
                    </div>
                </div>

                <!-- 5 Reactive KPI Summary Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                    
                    <!-- Due Today -->
                    <div @click="setTimeline('today')"
                         :class="timeline === 'today' ? 'ring-2 ring-rose-500 bg-rose-50/80 dark:bg-rose-950/40' : 'bg-slate-50/80 dark:bg-slate-800/50 hover:bg-rose-50/50 dark:hover:bg-rose-950/20'"
                         class="cursor-pointer p-4 rounded-2xl border border-slate-200/70 dark:border-slate-800 transition duration-150">
                        <div class="flex items-center justify-between text-rose-600 dark:text-rose-400">
                            <span class="text-[11px] font-bold uppercase tracking-wider">🚨 Due Today</span>
                            <span class="text-sm">⚡</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400 mt-1 font-mono" x-text="counts.due_today">
                            {{ $counts['due_today'] }}
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium mt-0.5 block">Requires immediate action</span>
                    </div>

                    <!-- Overdue -->
                    <div @click="setTimeline('overdue')"
                         :class="timeline === 'overdue' ? 'ring-2 ring-amber-500 bg-amber-50/80 dark:bg-amber-950/40' : 'bg-slate-50/80 dark:bg-slate-800/50 hover:bg-amber-50/50 dark:hover:bg-amber-950/20'"
                         class="cursor-pointer p-4 rounded-2xl border border-slate-200/70 dark:border-slate-800 transition duration-150">
                        <div class="flex items-center justify-between text-amber-600 dark:text-amber-400">
                            <span class="text-[11px] font-bold uppercase tracking-wider">⚠️ Overdue</span>
                            <span class="text-sm">⏳</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400 mt-1 font-mono" x-text="counts.overdue">
                            {{ $counts['overdue'] }}
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium mt-0.5 block">Passed target commitment</span>
                    </div>

                    <!-- Upcoming -->
                    <div @click="setTimeline('upcoming')"
                         :class="timeline === 'upcoming' ? 'ring-2 ring-blue-500 bg-blue-50/80 dark:bg-blue-950/40' : 'bg-slate-50/80 dark:bg-slate-800/50 hover:bg-blue-50/50 dark:hover:bg-blue-950/20'"
                         class="cursor-pointer p-4 rounded-2xl border border-slate-200/70 dark:border-slate-800 transition duration-150">
                        <div class="flex items-center justify-between text-blue-600 dark:text-blue-400">
                            <span class="text-[11px] font-bold uppercase tracking-wider">📅 Upcoming</span>
                            <span class="text-sm">🗓️</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-blue-600 dark:text-blue-400 mt-1 font-mono" x-text="counts.upcoming">
                            {{ $counts['upcoming'] }}
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium mt-0.5 block">Next scheduled days</span>
                    </div>

                    <!-- Resolved -->
                    <div @click="setTimeline('resolved')"
                         :class="timeline === 'resolved' ? 'ring-2 ring-emerald-500 bg-emerald-50/80 dark:bg-emerald-950/40' : 'bg-slate-50/80 dark:bg-slate-800/50 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20'"
                         class="cursor-pointer p-4 rounded-2xl border border-slate-200/70 dark:border-slate-800 transition duration-150">
                        <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                            <span class="text-[11px] font-bold uppercase tracking-wider">✅ Resolved</span>
                            <span class="text-sm">🎉</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono" x-text="counts.resolved_this_month">
                            {{ $counts['resolved_this_month'] }}
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium mt-0.5 block">Resolved this month</span>
                    </div>

                    <!-- Total Active -->
                    <div @click="setTimeline('all_active')"
                         :class="timeline === 'all_active' ? 'ring-2 ring-indigo-500 bg-indigo-50/80 dark:bg-indigo-950/40' : 'bg-slate-50/80 dark:bg-slate-800/50 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20'"
                         class="col-span-2 sm:col-span-1 cursor-pointer p-4 rounded-2xl border border-slate-200/70 dark:border-slate-800 transition duration-150">
                        <div class="flex items-center justify-between text-indigo-600 dark:text-indigo-400">
                            <span class="text-[11px] font-bold uppercase tracking-wider">📊 Total Active</span>
                            <span class="text-sm">📋</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-1 font-mono" x-text="counts.total_active">
                            {{ $counts['total_active'] }}
                        </div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium mt-0.5 block">All open commitments</span>
                    </div>

                </div>
            </div>

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
                                            <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono cursor-pointer hover:underline"
                                                  @click="copyText(item.mobile, 'Mobile copied!')"
                                                  title="Click to copy mobile">
                                                📱 <span x-text="item.mobile"></span>
                                            </span>
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

        </div>

        <!-- ================= MODALS & DRAWERS ================= -->

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
                    
                    <!-- CA Number Input -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Consumer CA Number *</label>
                        <input type="text"
                               x-model="form.ca_number"
                               required
                               placeholder="e.g. 10230041576"
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-emerald-500">
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

        <!-- 2. COMPLETE / RESOLVE MODAL -->
        <div x-show="modals.complete"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="modals.complete = false"
                 class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>✅</span> Complete FieldDesk Action
                    </h3>
                    <button @click="modals.complete = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>

                <form @submit.prevent="submitComplete()" class="space-y-3.5 text-xs">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400">Resolving action for CA:</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-white ml-1" x-text="activeAction?.ca_number"></span>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Amount Collected (₹)</label>
                        <input type="number"
                               step="0.01"
                               x-model="completeForm.collected_amount"
                               placeholder="0.00"
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Resolution Remark</label>
                        <input type="text"
                               x-model="completeForm.note"
                               placeholder="e.g. Paid in full via PhonePe / Meter inspected OK"
                               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="modals.complete = false" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md shadow-emerald-500/20">
                            Confirm Resolution
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- 3. TIMELINE DRAWER -->
        <div x-show="modals.timeline"
             x-cloak
             class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-sm flex justify-end">
            <div @click.away="modals.timeline = false"
                 class="w-full max-w-md bg-white dark:bg-slate-900 h-full p-6 shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>🕒</span> Touch History Timeline
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Audit log of touches and status changes
                        </p>
                    </div>
                    <button @click="modals.timeline = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>

                <div class="flex-1 overflow-y-auto py-4 space-y-4">
                    <template x-if="timelineActivities.length === 0">
                        <div class="text-center py-10 text-xs text-slate-400">
                            No history records logged yet.
                        </div>
                    </template>
                    <template x-for="act in timelineActivities" :key="act.id">
                        <div class="flex items-start gap-3 text-xs">
                            <div class="mt-0.5 w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs shrink-0">
                                <template x-if="act.action_type === 'created'"><span>➕</span></template>
                                <template x-if="act.action_type === 'rescheduled'"><span>🔄</span></template>
                                <template x-if="act.action_type === 'completed'"><span>✅</span></template>
                                <template x-if="act.action_type === 'whatsapp_sent'"><span>💬</span></template>
                                <template x-if="act.action_type === 'call_made'"><span>📞</span></template>
                                <template x-if="!['created','rescheduled','completed','whatsapp_sent','call_made'].includes(act.action_type)"><span>📝</span></template>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 capitalize" x-text="act.action_type.replace('_', ' ')"></span>
                                    <span class="text-[10px] text-slate-400" x-text="act.created_at"></span>
                                </div>
                                <div class="text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed" x-text="act.note"></div>
                                <template x-if="act.amount_recorded > 0">
                                    <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                        Amount: ₹<span x-text="Number(act.amount_recorded).toLocaleString('en-IN')"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button @click="modals.timeline = false" class="w-full py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold">
                        Close Drawer
                    </button>
                </div>

            </div>
        </div>

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

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Private Field Note</label>
                        <textarea x-model="editForm.private_note"
                                  rows="2"
                                  class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
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

        <!-- Toast Notification -->
        <div x-show="toast.show"
             x-cloak
             x-transition
             class="fixed bottom-5 right-5 z-50 bg-slate-900 text-white text-xs px-4 py-2.5 rounded-xl shadow-lg border border-slate-700 flex items-center gap-2">
            <span x-text="toast.icon"></span>
            <span x-text="toast.message"></span>
        </div>

    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function fieldDeskApp() {
            return {
                loading: false,
                timeline: 'all_active',
                categoryId: '',
                mruId: '',
                priority: '',
                search: @json($initialCa ?? ''),
                page: 1,
                items: [],
                counts: {
                    due_today: {{ $counts['due_today'] }},
                    overdue: {{ $counts['overdue'] }},
                    upcoming: {{ $counts['upcoming'] }},
                    resolved_this_month: {{ $counts['resolved_this_month'] }},
                    total_active: {{ $counts['total_active'] }}
                },
                pagination: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 25,
                    total: 0
                },
                modals: {
                    create: false,
                    complete: false,
                    timeline: false,
                    edit: false
                },
                form: {
                    ca_number: '',
                    category_id: '{{ $categories->first()?->id ?? 1 }}',
                    target_date: new Date().toISOString().split('T')[0],
                    priority: 'normal',
                    mru_id: '',
                    target_amount: '',
                    payment_mode: '',
                    private_note: ''
                },
                editForm: {
                    id: null,
                    category_id: '',
                    target_date: '',
                    priority: 'normal',
                    target_amount: '',
                    payment_mode: '',
                    private_note: ''
                },
                completeForm: {
                    id: null,
                    collected_amount: '',
                    note: ''
                },
                activeAction: null,
                timelineActivities: [],
                toast: {
                    show: false,
                    message: '',
                    icon: '✅'
                },

                async initApp() {
                    await this.fetchData();
                    // If initial CA passed via deep link, open create modal if not found
                    if (@json($initialCa ?? '')) {
                        if (this.items.length === 0) {
                            this.openCreateModal(@json($initialCa));
                        }
                    }
                },

                setTimeline(t) {
                    this.timeline = t;
                    this.page = 1;
                    this.fetchData();
                },

                goToPage(p) {
                    this.page = p;
                    this.fetchData();
                },

                async fetchData() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            timeline: this.timeline,
                            category_id: this.categoryId,
                            mru_id: this.mruId,
                            priority: this.priority,
                            search: this.search,
                            page: this.page,
                            per_page: 25
                        });
                        const res = await fetch(`{{ route('api.field-desk.data') }}?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.items = json.data;
                            this.counts = json.counts;
                            this.pagination = json.pagination;
                        }
                    } catch (e) {
                        this.showToast('Failed to load data', '❌');
                    } finally {
                        this.loading = false;
                    }
                },

                setDatePreset(days) {
                    const d = new Date();
                    d.setDate(d.getDate() + days);
                    this.form.target_date = d.toISOString().split('T')[0];
                },

                openCreateModal(ca = '') {
                    this.form.ca_number = ca || this.search || '';
                    this.form.target_date = new Date().toISOString().split('T')[0];
                    this.form.target_amount = '';
                    this.form.payment_mode = '';
                    this.form.private_note = '';
                    this.modals.create = true;
                },

                async submitCreate() {
                    try {
                        const res = await fetch(`{{ route('api.field-desk.store') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.form)
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.modals.create = false;
                            this.showToast('Action created successfully in FieldDesk', '⚡');
                            this.fetchData();
                        } else {
                            this.showToast(json.message || 'Validation error', '⚠️');
                        }
                    } catch (e) {
                        this.showToast('Error saving action', '❌');
                    }
                },

                openEditModal(item) {
                    this.editForm = {
                        id: item.id,
                        category_id: item.category_id,
                        target_date: item.target_date,
                        priority: item.priority,
                        target_amount: item.target_amount || '',
                        payment_mode: item.payment_mode || '',
                        private_note: item.private_note || ''
                    };
                    this.modals.edit = true;
                },

                async submitEdit() {
                    try {
                        const res = await fetch(`/api/field-desk/actions/${this.editForm.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.editForm)
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.modals.edit = false;
                            this.showToast('Action updated successfully', '✅');
                            this.fetchData();
                        } else {
                            this.showToast(json.message || 'Validation error', '⚠️');
                        }
                    } catch (e) {
                        this.showToast('Error updating action', '❌');
                    }
                },

                async quickReschedule(id, days) {
                    try {
                        const res = await fetch(`/api/field-desk/actions/${id}/reschedule`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ days: days })
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.showToast(`Snoozed +${days} days!`, '🔄');
                            this.fetchData();
                        }
                    } catch (e) {
                        this.showToast('Failed to snooze action', '❌');
                    }
                },

                openCompleteModal(item) {
                    this.activeAction = item;
                    this.completeForm = {
                        id: item.id,
                        collected_amount: item.target_amount > 0 ? (item.remaining_amount || item.target_amount) : '',
                        note: ''
                    };
                    this.modals.complete = true;
                },

                async submitComplete() {
                    try {
                        const res = await fetch(`/api/field-desk/actions/${this.completeForm.id}/complete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.completeForm)
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.modals.complete = false;
                            this.showToast('Marked as completed / resolved! 🎉', '✅');
                            this.fetchData();
                        }
                    } catch (e) {
                        this.showToast('Error completing action', '❌');
                    }
                },

                async openTimelineDrawer(id) {
                    this.timelineActivities = [];
                    this.modals.timeline = true;
                    try {
                        const res = await fetch(`/api/field-desk/actions/${id}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.timelineActivities = json.activities || [];
                        }
                    } catch (e) {
                        this.showToast('Could not load history', '❌');
                    }
                },

                async logCommunication(id, type, note) {
                    try {
                        await fetch(`/api/field-desk/actions/${id}/activity`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ action_type: type, note: note })
                        });
                    } catch (e) {}
                },

                async deleteAction(id) {
                    if (!confirm('Are you sure you want to remove this action?')) return;
                    try {
                        const res = await fetch(`/api/field-desk/actions/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.showToast('Action removed', '🗑️');
                            this.fetchData();
                        }
                    } catch (e) {
                        this.showToast('Error removing action', '❌');
                    }
                },

                copyText(text, msg) {
                    navigator.clipboard.writeText(text);
                    this.showToast(msg, '📋');
                },

                showToast(msg, icon = '✅') {
                    this.toast.message = msg;
                    this.toast.icon = icon;
                    this.toast.show = true;
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3000);
                }
            };
        }
    </script>
</x-app-layout>
