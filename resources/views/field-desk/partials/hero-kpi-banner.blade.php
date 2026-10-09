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