@extends('layouts.user-panel')

@section('header', 'My Bug Reports & Support Tickets')

@section('content')
<div x-data="userIssuesTracker()" class="space-y-6">

    <!-- Top Breadcrumb & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('user-panel.index') }}" class="hover:text-brand-600 dark:hover:text-cyan-400 transition">User Hub</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">Bug Reports & Tickets</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>🐞</span>
                <span>My Bug Reports & Tracking</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Track issues you have reported, inspect live triage progress, and read AI agent resolution notes.
            </p>
        </div>

        <button type="button" 
                @click="openNewReportModal()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-xs font-bold shadow-md shadow-indigo-500/20 transition active:scale-95 cursor-pointer">
            <span>➕</span>
            <span>Report a New Bug</span>
        </button>
    </div>

    <!-- Quick Reference Code Lookup Card -->
    <div class="rounded-3xl border border-indigo-200/80 dark:border-indigo-900/60 bg-gradient-to-br from-indigo-50/50 via-white to-purple-50/30 dark:from-indigo-950/30 dark:via-slate-900 dark:to-purple-950/20 p-5 sm:p-6 shadow-sm">
        <div class="max-w-2xl">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="text-lg">🔍</span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Track Ticket by Reference Number</h3>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-400 mb-4">
                Have a ticket reference code (e.g., <code class="font-mono px-1.5 py-0.5 rounded bg-indigo-100/70 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 font-bold">BUG-20260918-3KS3</code>)? Enter it below to immediately check its status, progress, and AI fix notes.
            </p>

            <form @submit.prevent="lookupTicket()" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <div class="relative flex-1">
                    <input type="text" 
                           x-model="lookupCode"
                           placeholder="Enter Reference Code (e.g., BUG-20260918-XXXX)" 
                           required
                           class="w-full text-xs font-mono font-semibold uppercase tracking-wider px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:normal-case placeholder:font-sans placeholder:font-normal focus:ring-2 focus:ring-indigo-500 shadow-xs">
                    <button type="button" 
                            x-show="lookupCode" 
                            @click="lookupCode = ''; lookupResult = null; lookupError = null" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 text-xs">
                        ✕
                    </button>
                </div>
                <button type="submit" 
                        :disabled="isSearching"
                        class="px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition active:scale-95 disabled:opacity-60 flex items-center justify-center gap-2 shrink-0">
                    <span x-show="isSearching" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                    <span x-text="isSearching ? 'Checking...' : 'Check Status 🚀'"></span>
                </button>
            </form>

            <!-- Lookup Error Message -->
            <div x-show="lookupError" x-cloak class="mt-3 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900/60 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
                <span>⚠️</span>
                <span x-text="lookupError"></span>
            </div>

            <!-- Lookup Result Display Box -->
            <div x-show="lookupResult" x-cloak class="mt-4 p-5 rounded-2xl bg-white dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 shadow-lg space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-700/80 pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="font-mono font-black text-sm text-indigo-600 dark:text-indigo-400" x-text="lookupResult?.issue_code"></span>
                        <button type="button" @click="copyText(lookupResult?.issue_code)" class="text-[11px] px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 transition">
                            📋 Copy
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Status Badge -->
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold flex items-center gap-1.5"
                              :class="{
                                  'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-800': lookupResult?.status === 'pending',
                                  'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-300 dark:border-blue-800': lookupResult?.status === 'verified',
                                  'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-300 dark:border-purple-800': lookupResult?.status === 'in_progress',
                                  'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800': lookupResult?.status === 'resolved',
                                  'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 dark:border-rose-800': lookupResult?.status === 'spam'
                              }">
                            <span x-show="lookupResult?.status === 'pending'">⏳</span>
                            <span x-show="lookupResult?.status === 'verified'">🔍</span>
                            <span x-show="lookupResult?.status === 'in_progress'">🛠️</span>
                            <span x-show="lookupResult?.status === 'resolved'">✅</span>
                            <span x-show="lookupResult?.status === 'spam'">⚠️</span>
                            <span x-text="lookupResult?.status_label"></span>
                        </span>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white" x-text="lookupResult?.title"></h4>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed" x-text="lookupResult?.description"></p>
                </div>

                <!-- AI Resolution Notes (If Resolved) -->
                <div x-show="lookupResult?.ai_resolution_notes" class="p-3.5 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 space-y-1">
                    <div class="flex items-center gap-1.5 font-bold text-xs">
                        <span>🤖</span>
                        <span>AI Agent Resolution & Fix Notes:</span>
                    </div>
                    <p class="text-xs leading-relaxed text-emerald-800 dark:text-emerald-300 font-mono" x-text="lookupResult?.ai_resolution_notes"></p>
                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400 pt-1 flex items-center gap-1" x-show="lookupResult?.resolved_at">
                        <span>Resolved on:</span>
                        <span x-text="lookupResult?.resolved_at"></span>
                        <span class="text-slate-400">·</span>
                        <span x-text="'(' + lookupResult?.resolved_at_human + ')'"></span>
                    </div>
                </div>

                <!-- Metadata Row -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-2 border-t border-slate-100 dark:border-slate-700/60 text-[11px] text-slate-500 dark:text-slate-400">
                    <div>Category: <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="lookupResult?.category_label"></span></div>
                    <div>Severity: <span class="font-semibold uppercase text-slate-700 dark:text-slate-200" x-text="lookupResult?.severity"></span></div>
                    <div>Reported: <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="lookupResult?.created_at_human"></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center text-xl font-bold">
                📑
            </div>
            <div>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Total Reported</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white">{{ $stats['total'] }}</span>
            </div>
        </div>

        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl font-bold">
                ⏳
            </div>
            <div>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Under Review / Active</span>
                <span class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ $stats['active'] }}</span>
            </div>
        </div>

        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl font-bold">
                ✅
            </div>
            <div>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Resolved & Fixed</span>
                <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $stats['resolved'] }}</span>
            </div>
        </div>
    </div>

    <!-- Filter Pills & Search Bar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <!-- Status Filter Tabs -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800/80 rounded-2xl overflow-x-auto">
            <a href="{{ route('user-panel.issues', ['status' => 'all', 'q' => $search]) }}" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $status === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                All ({{ $stats['total'] }})
            </a>
            <a href="{{ route('user-panel.issues', ['status' => 'active', 'q' => $search]) }}" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $status === 'active' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                Active ({{ $stats['active'] }})
            </a>
            <a href="{{ route('user-panel.issues', ['status' => 'resolved', 'q' => $search]) }}" 
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $status === 'resolved' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                Resolved ({{ $stats['resolved'] }})
            </a>
        </div>

        <!-- Search Input -->
        <form method="GET" action="{{ route('user-panel.issues') }}" class="relative w-full sm:w-72">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="text" 
                   name="q" 
                   value="{{ $search }}" 
                   placeholder="Search title, ticket code, CA..." 
                   class="w-full text-xs font-medium pl-9 pr-4 py-2 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
            @if(!empty($search))
                <a href="{{ route('user-panel.issues', ['status' => $status]) }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">✕</a>
            @endif
        </form>
    </div>

    <!-- Tickets List Table / Card Stream -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden">
        @if($issues->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800 text-3xl flex items-center justify-center mx-auto text-slate-400">
                    🎉
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No Bug Reports Found</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                    @if(!empty($search) || $status !== 'all')
                        No tickets match your active filter. Try resetting your search or filter.
                    @else
                        You haven't reported any issues yet. If you encounter any bugs or calculation problems while working, use the floating bug icon anytime to let our AI agent investigate!
                    @endif
                </p>
                <div class="pt-2">
                    <button type="button" @click="openNewReportModal()" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                        Report an Issue Now
                    </button>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40 text-slate-500 dark:text-slate-400 font-bold uppercase text-[10px] tracking-wider">
                            <th class="px-5 py-3.5">Ticket Reference</th>
                            <th class="px-4 py-3.5">Issue Title & Details</th>
                            <th class="px-4 py-3.5">Category</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5">Reported</th>
                            <th class="px-5 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($issues as $issue)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                <!-- Reference Code -->
                                <td class="px-5 py-4 whitespace-nowrap font-mono font-bold">
                                    <div class="flex items-center gap-2">
                                        <span class="text-indigo-600 dark:text-indigo-400">{{ $issue->issue_code }}</span>
                                        <button type="button" 
                                                @click="copyText('{{ $issue->issue_code }}')" 
                                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded" 
                                                title="Copy Ticket Code">
                                            📋
                                        </button>
                                    </div>
                                    @if($issue->severity === 'critical')
                                        <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                            Critical
                                        </span>
                                    @endif
                                </td>

                                <!-- Title & Excerpt -->
                                <td class="px-4 py-4 max-w-sm">
                                    <div class="font-bold text-slate-900 dark:text-white truncate">{{ $issue->title }}</div>
                                    <div class="text-slate-500 dark:text-slate-400 text-[11px] truncate mt-0.5">{{ $issue->description }}</div>
                                    @if(!empty($issue->ai_resolution_notes))
                                        <div class="mt-1.5 flex items-center gap-1.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                            <span>🤖 Fix Deployed:</span>
                                            <span class="truncate max-w-xs font-mono font-normal">{{ $issue->ai_resolution_notes }}</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Category -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ $issue->getCategoryLabel() }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold
                                        @if($issue->status === 'pending') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-800
                                        @elseif($issue->status === 'verified') bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-300 dark:border-blue-800
                                        @elseif($issue->status === 'in_progress') bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-300 dark:border-purple-800
                                        @elseif($issue->status === 'resolved') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800
                                        @elseif($issue->status === 'spam') bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 dark:border-rose-800
                                        @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300
                                        @endif">
                                        <span>
                                            @if($issue->status === 'pending') ⏳
                                            @elseif($issue->status === 'verified') 🔍
                                            @elseif($issue->status === 'in_progress') 🛠️
                                            @elseif($issue->status === 'resolved') ✅
                                            @elseif($issue->status === 'spam') ⚠️
                                            @else 📌
                                            @endif
                                        </span>
                                        <span>{{ $issue->getStatusLabel() }}</span>
                                    </span>
                                </td>

                                <!-- Reported Date -->
                                <td class="px-4 py-4 whitespace-nowrap text-slate-500 dark:text-slate-400 text-[11px]">
                                    <div>{{ $issue->created_at ? $issue->created_at->format('M d, Y') : 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500">{{ $issue->created_at ? $issue->created_at->diffForHumans() : '' }}</div>
                                </td>

                                <!-- Action -->
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <button type="button" 
                                            @click="viewIssueDetails({{ json_encode([
                                                'issue_code' => $issue->issue_code,
                                                'title' => $issue->title,
                                                'description' => $issue->description,
                                                'category_label' => $issue->getCategoryLabel(),
                                                'severity' => $issue->severity,
                                                'status' => $issue->status,
                                                'status_label' => $issue->getStatusLabel(),
                                                'status_color' => $issue->getStatusColor(),
                                                'ai_resolution_notes' => $issue->ai_resolution_notes,
                                                'created_at' => $issue->created_at ? $issue->created_at->format('M d, Y h:i A') : null,
                                                'created_at_human' => $issue->created_at ? $issue->created_at->diffForHumans() : null,
                                                'verified_at' => $issue->verified_at ? $issue->verified_at->format('M d, Y h:i A') : null,
                                                'resolved_at' => $issue->resolved_at ? $issue->resolved_at->format('M d, Y h:i A') : null,
                                                'resolved_at_human' => $issue->resolved_at ? $issue->resolved_at->diffForHumans() : null,
                                                'ca_number' => $issue->ca_number,
                                                'mru_name' => $issue->mru ? ($issue->mru->code . ' - ' . $issue->mru->name) : null,
                                            ]) }})"
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200/60 dark:border-indigo-800/60 transition">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($issues->hasPages())
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40">
                    {{ $issues->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Ticket Details Modal -->
    <div x-show="detailsModalOpen" 
         x-cloak 
         @keydown.escape.window="detailsModalOpen = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6">
        <div @click.away="detailsModalOpen = false" 
             class="w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl font-bold">
                        🐞
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-black text-sm text-indigo-600 dark:text-indigo-400" x-text="selectedIssue?.issue_code"></span>
                            <button type="button" @click="copyText(selectedIssue?.issue_code)" class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700">📋</button>
                        </div>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300" x-text="selectedIssue?.category_label"></h3>
                    </div>
                </div>
                <button type="button" @click="detailsModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    ✕
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto space-y-5 text-xs">
                
                <!-- Status Banner -->
                <div class="p-4 rounded-2xl flex items-center justify-between"
                     :class="{
                         'bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200': selectedIssue?.status === 'pending',
                         'bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200': selectedIssue?.status === 'verified',
                         'bg-purple-50 dark:bg-purple-950/50 border border-purple-200 dark:border-purple-800 text-purple-800 dark:text-purple-200': selectedIssue?.status === 'in_progress',
                         'bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200': selectedIssue?.status === 'resolved',
                         'bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200': selectedIssue?.status === 'spam'
                     }">
                    <div>
                        <div class="font-bold text-sm flex items-center gap-1.5">
                            <span x-show="selectedIssue?.status === 'pending'">⏳ Status: Pending Review</span>
                            <span x-show="selectedIssue?.status === 'verified'">🔍 Status: Verified by Team</span>
                            <span x-show="selectedIssue?.status === 'in_progress'">🛠️ Status: Fix In Progress</span>
                            <span x-show="selectedIssue?.status === 'resolved'">✅ Status: Resolved & Fixed</span>
                            <span x-show="selectedIssue?.status === 'spam'">⚠️ Status: Closed / Discarded</span>
                        </div>
                        <p class="text-[11px] opacity-80 mt-0.5">
                            <span x-show="selectedIssue?.status === 'pending'">We have received your issue report. It is queued for admin verification and diagnostic triage.</span>
                            <span x-show="selectedIssue?.status === 'verified'">The issue has been verified and passed to the AI engineering agent to inspect and resolve.</span>
                            <span x-show="selectedIssue?.status === 'in_progress'">The engineering team or AI agent is actively modifying and testing code fixes.</span>
                            <span x-show="selectedIssue?.status === 'resolved'">The bug has been fixed, tested with automated test suites, and verified in the codebase!</span>
                            <span x-show="selectedIssue?.status === 'spam'">This submission was marked as duplicate or non-reproducible.</span>
                        </p>
                    </div>
                </div>

                <!-- Problem Description -->
                <div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm" x-text="selectedIssue?.title"></h4>
                    <div class="mt-2 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 text-slate-700 dark:text-slate-300 leading-relaxed font-sans whitespace-pre-wrap" x-text="selectedIssue?.description"></div>
                </div>

                <!-- AI Resolution Notes -->
                <div x-show="selectedIssue?.ai_resolution_notes" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 space-y-1.5">
                    <div class="flex items-center gap-2 font-bold text-emerald-800 dark:text-emerald-200 text-xs">
                        <span>🤖</span>
                        <span>AI Agent Fix & Resolution Summary</span>
                    </div>
                    <div class="text-xs text-emerald-900 dark:text-emerald-300 font-mono leading-relaxed bg-white/70 dark:bg-slate-900/60 p-3 rounded-xl border border-emerald-200/60 dark:border-emerald-800/60" x-text="selectedIssue?.ai_resolution_notes"></div>
                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400 pt-1 flex items-center justify-between" x-show="selectedIssue?.resolved_at">
                        <span>Resolved: <strong x-text="selectedIssue?.resolved_at"></strong></span>
                        <span x-text="selectedIssue?.resolved_at_human"></span>
                    </div>
                </div>

                <!-- Contextual Details -->
                <div class="grid grid-cols-2 gap-3 text-[11px] p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 font-mono text-slate-600 dark:text-slate-400">
                    <div>Submitted: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="selectedIssue?.created_at"></span></div>
                    <div>Severity: <span class="font-bold uppercase text-slate-800 dark:text-slate-200" x-text="selectedIssue?.severity"></span></div>
                    <div x-show="selectedIssue?.mru_name">MRU: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="selectedIssue?.mru_name"></span></div>
                    <div x-show="selectedIssue?.ca_number">CA Number: <span class="font-bold text-indigo-600 dark:text-cyan-400" x-text="selectedIssue?.ca_number"></span></div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-end">
                <button type="button" @click="detailsModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white text-xs font-bold transition">
                    Close Details
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification for Copy -->
    <div x-show="toastMessage" 
         x-cloak 
         x-transition 
         class="fixed bottom-6 right-6 z-50 px-4 py-2.5 rounded-2xl bg-slate-900 text-white text-xs font-bold shadow-xl flex items-center gap-2">
        <span>📋</span>
        <span x-text="toastMessage"></span>
    </div>
</div>

<script>
function userIssuesTracker() {
    return {
        lookupCode: '',
        isSearching: false,
        lookupResult: null,
        lookupError: null,
        detailsModalOpen: false,
        selectedIssue: null,
        toastMessage: null,

        lookupTicket() {
            if (!this.lookupCode) return;
            this.isSearching = true;
            this.lookupError = null;
            this.lookupResult = null;

            fetch('/issues/track/' + encodeURIComponent(this.lookupCode.trim()), {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                this.isSearching = false;
                if (data.success && data.issue) {
                    this.lookupResult = data.issue;
                } else {
                    this.lookupError = data.message || 'No issue found with this reference code.';
                }
            })
            .catch(e => {
                this.isSearching = false;
                this.lookupError = 'Network error while checking ticket. Please try again.';
            });
        },

        viewIssueDetails(issue) {
            this.selectedIssue = issue;
            this.detailsModalOpen = true;
        },

        openNewReportModal() {
            window.dispatchEvent(new CustomEvent('open-bug-reporter'));
        },

        copyText(text) {
            if (!text) return;
            navigator.clipboard.writeText(text);
            this.toastMessage = 'Copied ' + text + ' to clipboard!';
            setTimeout(() => this.toastMessage = null, 3000);
        }
    };
}
</script>
@endsection
