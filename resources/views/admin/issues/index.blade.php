@extends('layouts.admin', ['title' => 'Bug Tracker & AI Desk'])

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-500 to-indigo-600 flex items-center justify-center text-2xl shadow-lg shadow-indigo-500/20 shrink-0">
                🐞
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Bug Tracker & AI Desk</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-0.5">Triage reported issues, reject spam, and generate AI diagnostic bundles to fix bugs.</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-indigo-950/60 text-indigo-300 border border-indigo-800/80">
                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                <span>AI Agent Ready</span>
            </span>
        </div>
    </div>

    <!-- Status KPI Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-4">
        <a href="{{ route('admin.issues.index', ['status' => 'all']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'all' ? 'bg-indigo-950/40 border-indigo-500 shadow-lg' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">All Reports</span>
            <div class="text-2xl font-black text-white font-mono mt-1">{{ $counts['all'] }}</div>
        </a>

        <a href="{{ route('admin.issues.index', ['status' => 'pending']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'pending' ? 'bg-amber-950/40 border-amber-500 shadow-lg' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">Pending Triage</span>
                @if($counts['pending'] > 0)
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                @endif
            </div>
            <div class="text-2xl font-black text-amber-400 font-mono mt-1">{{ $counts['pending'] }}</div>
        </a>

        <a href="{{ route('admin.issues.index', ['status' => 'verified']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'verified' ? 'bg-rose-950/40 border-rose-500 shadow-lg' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
            <span class="text-[11px] font-bold text-rose-400 uppercase tracking-wider">Verified Bugs</span>
            <div class="text-2xl font-black text-rose-400 font-mono mt-1">{{ $counts['verified'] }}</div>
        </a>

        <a href="{{ route('admin.issues.index', ['status' => 'resolved']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'resolved' ? 'bg-emerald-950/40 border-emerald-500 shadow-lg' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
            <span class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Resolved</span>
            <div class="text-2xl font-black text-emerald-400 font-mono mt-1">{{ $counts['resolved'] }}</div>
        </a>

        <a href="{{ route('admin.issues.index', ['status' => 'spam']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'spam' ? 'bg-slate-900 border-slate-600 shadow-lg' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Spam / Dismissed</span>
            <div class="text-2xl font-black text-slate-500 font-mono mt-1">{{ $counts['spam'] }}</div>
        </a>
    </div>

    <!-- Filters & Search Form -->
    <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.issues.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <input type="hidden" name="status" value="{{ $status }}">

            <!-- Search -->
            <div class="relative min-w-[220px]">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 text-xs">🔍</span>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search title, CA, user, code..."
                       class="w-full text-xs bg-slate-900 border border-slate-800 rounded-xl pl-8 pr-3 py-2 text-white placeholder-slate-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <!-- Severity Filter -->
            <select name="severity" onchange="this.form.submit()" class="text-xs bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-slate-300 focus:ring-1 focus:ring-indigo-500">
                <option value="all" {{ $severity === 'all' ? 'selected' : '' }}>All Severities</option>
                <option value="critical" {{ $severity === 'critical' ? 'selected' : '' }}>🔴 Critical</option>
                <option value="high" {{ $severity === 'high' ? 'selected' : '' }}>🟠 High</option>
                <option value="medium" {{ $severity === 'medium' ? 'selected' : '' }}>🟡 Medium</option>
                <option value="low" {{ $severity === 'low' ? 'selected' : '' }}>🟢 Low</option>
            </select>

            <!-- Category Filter -->
            <select name="category" onchange="this.form.submit()" class="text-xs bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-slate-300 focus:ring-1 focus:ring-indigo-500">
                <option value="all" {{ $category === 'all' ? 'selected' : '' }}>All Categories</option>
                <option value="calculation" {{ $category === 'calculation' ? 'selected' : '' }}>⚡ Calculation</option>
                <option value="bill_download" {{ $category === 'bill_download' ? 'selected' : '' }}>📑 Bill Download</option>
                <option value="mru_sync" {{ $category === 'mru_sync' ? 'selected' : '' }}>🗂️ MRU / Cycles</option>
                <option value="ui_display" {{ $category === 'ui_display' ? 'selected' : '' }}>🖥️ UI / Display</option>
                <option value="wallet_payment" {{ $category === 'wallet_payment' ? 'selected' : '' }}>👛 Wallet / Pay</option>
                <option value="other" {{ $category === 'other' ? 'selected' : '' }}>❓ Other</option>
            </select>

            <button type="submit" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                Filter
            </button>

            @if($status !== 'all' || $severity !== 'all' || $category !== 'all' || !empty($search))
                <a href="{{ route('admin.issues.index') }}" class="text-xs text-slate-400 hover:text-white underline">Reset</a>
            @endif
        </form>

        <div class="text-xs text-slate-500 font-mono">
            Showing {{ $issues->firstItem() ?? 0 }}–{{ $issues->lastItem() ?? 0 }} of {{ $issues->total() }}
        </div>
    </div>

    <!-- Issues Table -->
    <div class="bg-slate-950 rounded-3xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-900/80 border-b border-slate-800 uppercase font-black tracking-wider text-[10px] text-slate-400">
                    <tr>
                        <th class="py-3.5 px-4">Ticket</th>
                        <th class="py-3.5 px-4">Title & Description</th>
                        <th class="py-3.5 px-4 text-center">Category</th>
                        <th class="py-3.5 px-4 text-center">Severity</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4">Reporter</th>
                        <th class="py-3.5 px-4">Context</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    @forelse($issues as $issue)
                        <tr class="hover:bg-slate-900/40 transition">
                            <!-- Ticket Code -->
                            <td class="py-4 px-4 font-mono font-bold whitespace-nowrap">
                                <a href="{{ route('admin.issues.show', $issue) }}" class="text-indigo-400 hover:text-indigo-300 hover:underline">
                                    {{ $issue->issue_code }}
                                </a>
                                <div class="text-[10px] text-slate-500 mt-0.5">{{ $issue->created_at->diffForHumans() }}</div>
                            </td>

                            <!-- Title & Description -->
                            <td class="py-4 px-4 max-w-sm">
                                <a href="{{ route('admin.issues.show', $issue) }}" class="font-bold text-white hover:text-indigo-400 transition line-clamp-1">
                                    {{ $issue->title }}
                                </a>
                                <p class="text-[11px] text-slate-400 line-clamp-2 mt-0.5">{{ $issue->description }}</p>
                            </td>

                            <!-- Category -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase bg-slate-900 border border-slate-800 text-slate-300">
                                    {{ str_replace('_', ' ', $issue->category) }}
                                </span>
                            </td>

                            <!-- Severity -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                @if($issue->severity === 'critical')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-rose-950/80 text-rose-300 border border-rose-800 animate-pulse">Critical</span>
                                @elseif($issue->severity === 'high')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-amber-950/80 text-amber-300 border border-amber-800">High</span>
                                @elseif($issue->severity === 'medium')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-800 text-slate-300">Medium</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase bg-slate-900 text-slate-400">Low</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                @if($issue->status === 'verified')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-950/60 text-rose-300 border border-rose-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Verified Bug
                                    </span>
                                @elseif($issue->status === 'resolved')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-950/60 text-emerald-300 border border-emerald-800">
                                        <span>✓</span> Resolved
                                    </span>
                                @elseif($issue->status === 'spam')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-900 text-slate-500 border border-slate-800">
                                        Spam
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-950/60 text-amber-300 border border-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span> Pending
                                    </span>
                                @endif
                            </td>

                            <!-- Reporter -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if($issue->user)
                                    <div class="font-semibold text-slate-200">{{ $issue->user->name }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $issue->user->email }}</div>
                                @else
                                    <span class="text-slate-500">Anonymous</span>
                                @endif
                            </td>

                            <!-- Context Metadata -->
                            <td class="py-4 px-4 text-[11px] font-mono whitespace-nowrap">
                                @if($issue->ca_number)
                                    <div>CA: <span class="text-blue-400 font-bold">{{ $issue->ca_number }}</span></div>
                                @endif
                                @if($issue->mru)
                                    <div class="text-slate-400">MRU: {{ $issue->mru->code }}</div>
                                @endif
                                @if($issue->billing_month && $issue->billing_year)
                                    <div class="text-slate-500">{{ $issue->billing_month }}/{{ $issue->billing_year }}</div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.issues.show', $issue) }}"
                                       class="px-2.5 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white font-bold transition text-[11px]"
                                       title="Inspect & Copy AI Fix Prompt">
                                        Inspect & AI Prompt 🤖
                                    </a>

                                    @if($issue->status === 'pending')
                                        <form method="POST" action="{{ route('admin.issues.verify', $issue) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white transition" title="Verify as real bug">
                                                ✓
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.issues.spam', $issue) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition" title="Mark as spam">
                                                🚫
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">🎉</div>
                                <div class="font-bold text-sm">No issue reports found!</div>
                                <p class="text-xs mt-1">Everything looks smooth. No active bugs matching your filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($issues->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-900/50">
                {{ $issues->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
