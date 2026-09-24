@extends('layouts.admin', ['title' => 'Issue ' . $issue->issue_code . ' — Bug Tracker'])

@section('content')
<div x-data="{ copied: false }" class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto space-y-6">

    <!-- Breadcrumb -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.issues.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-indigo-400 hover:text-indigo-300 transition">
            ← Back to Bug Tracker Desk
        </a>
        <div class="text-xs font-mono text-slate-500">Ticket: <span class="font-bold text-slate-300">{{ $issue->issue_code }}</span></div>
    </div>

    <!-- Issue Title Card & Action Bar -->
    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 flex-wrap mb-2">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-black font-mono bg-indigo-950/60 text-indigo-300 border border-indigo-800">
                        {{ $issue->issue_code }}
                    </span>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase bg-slate-900 border border-slate-800 text-slate-300">
                        {{ str_replace('_', ' ', $issue->category) }}
                    </span>
                    @if($issue->severity === 'critical')
                        <span class="px-2.5 py-1 rounded-lg text-xs font-black uppercase bg-rose-950 text-rose-300 border border-rose-800 animate-pulse">Critical</span>
                    @elseif($issue->severity === 'high')
                        <span class="px-2.5 py-1 rounded-lg text-xs font-black uppercase bg-amber-950 text-amber-300 border border-amber-800">High Severity</span>
                    @elseif($issue->severity === 'medium')
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase bg-slate-800 text-slate-300">Medium</span>
                    @else
                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold uppercase bg-slate-900 text-slate-400">Low</span>
                    @endif

                    @if($issue->status === 'verified')
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-950/80 text-rose-300 border border-rose-800">
                            ● Verified Bug (Queued for AI)
                        </span>
                    @elseif($issue->status === 'resolved')
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-800">
                            ✓ Resolved
                        </span>
                    @elseif($issue->status === 'spam')
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-900 text-slate-500 border border-slate-800">
                            Spam / Dismissed
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-950/80 text-amber-300 border border-amber-800">
                            ⏳ Pending Triage
                        </span>
                    @endif
                </div>

                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    {{ $issue->title }}
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Reported on <span class="font-mono text-slate-300">{{ $issue->created_at->format('M d, Y H:i:s') }}</span> by
                    <strong class="text-slate-200">{{ $issue->user ? $issue->user->name : 'Anonymous' }}</strong>
                    @if($issue->user)
                        <span class="font-mono text-slate-500">({{ $issue->user->email }})</span>
                    @endif
                </p>
            </div>

            <!-- Triage Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                @if($issue->status !== 'verified')
                    <form method="POST" action="{{ route('admin.issues.verify', $issue) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition shadow-md shadow-rose-600/20">
                            ✓ Verify as Real Bug
                        </button>
                    </form>
                @endif

                @if($issue->status !== 'spam')
                    <form method="POST" action="{{ route('admin.issues.spam', $issue) }}">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 border border-slate-700 text-slate-300 font-bold rounded-xl text-xs transition">
                            🚫 Mark Spam / Dismiss
                        </button>
                    </form>
                @endif

                @if($issue->status !== 'resolved')
                    <button type="button" @click="$dispatch('open-resolve-modal')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition shadow-md shadow-emerald-600/20">
                        ✓ Mark Resolved
                    </button>
                @endif
            </div>
        </div>

        <!-- Issue Description Box -->
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
            <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block mb-1">Reporter's Description</span>
            <p class="text-sm text-slate-200 whitespace-pre-wrap leading-relaxed">{{ $issue->description }}</p>
        </div>

        @if($issue->admin_notes)
            <div class="p-4 rounded-2xl bg-amber-950/30 border border-amber-800/60 text-amber-200 text-xs">
                <strong class="block font-bold mb-0.5">Admin Triage Remarks:</strong>
                {{ $issue->admin_notes }}
            </div>
        @endif

        @if($issue->ai_resolution_notes)
            <div class="p-4 rounded-2xl bg-emerald-950/30 border border-emerald-800/60 text-emerald-200 text-xs">
                <strong class="block font-bold mb-0.5">Resolution Notes (Fixed):</strong>
                {{ $issue->ai_resolution_notes }}
                <div class="text-[10px] text-emerald-400/80 font-mono mt-1">Resolved at {{ $issue->resolved_at }}</div>
            </div>
        @endif
    </div>

    <!-- AI AGENT DIAGNOSTIC COCKPIT (Core Feature) -->
    <div class="bg-gradient-to-br from-indigo-950/60 via-slate-950 to-slate-950 p-6 rounded-3xl border border-indigo-900/60 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/30 text-indigo-400 flex items-center justify-center text-xl font-bold">
                    🤖
                </div>
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span>AI Agent Diagnostic Bundle</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 font-mono">Antigravity Ready</span>
                    </h2>
                    <p class="text-xs text-slate-400">Hand this diagnostic bundle to the AI agent to reproduce, fix code, and run tests.</p>
                </div>
            </div>

            <!-- Copy AI Prompt Button -->
            <button type="button"
                    @click="navigator.clipboard.writeText($refs.aiPromptBox.value); copied = true; setTimeout(() => copied = false, 3000)"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition active:scale-95 cursor-pointer">
                <span x-show="!copied">📋 Copy AI Fix Prompt</span>
                <span x-show="copied" x-cloak class="text-emerald-300 font-bold">✓ Copied to Clipboard!</span>
            </button>
        </div>

        <!-- Terminal Command Shortcut -->
        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono text-slate-300 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 truncate">
                <span class="text-indigo-400 font-bold">CLI Command:</span>
                <code class="text-indigo-200 select-all">php artisan issue:show {{ $issue->issue_code }}</code>
            </div>
            <span class="text-[10px] text-slate-500 shrink-0">Terminal Agent Access</span>
        </div>

        <!-- Formatted Prompt Preview -->
        <div class="relative">
            <textarea x-ref="aiPromptBox"
                      readonly
                      rows="10"
                      class="w-full text-xs font-mono p-4 rounded-2xl bg-slate-950 border border-slate-800 text-indigo-200 select-all focus:ring-0 leading-relaxed">{{ $aiPrompt }}</textarea>
        </div>
    </div>

    <!-- Operational & Client Diagnostic Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Operational Context -->
        <div class="bg-slate-950 p-5 rounded-3xl border border-slate-800 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <span>📍</span> Operational Context
            </h3>
            <div class="space-y-2 text-xs font-mono">
                <div class="flex items-center justify-between py-1 border-b border-slate-900">
                    <span class="text-slate-500">Page URL:</span>
                    <span class="text-slate-200 font-bold truncate max-w-xs">{{ $issue->page_url ?: 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-900">
                    <span class="text-slate-500">CA Number:</span>
                    <span class="text-blue-400 font-bold">{{ $issue->ca_number ?: 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-900">
                    <span class="text-slate-500">MRU:</span>
                    <span class="text-slate-200 font-bold">{{ $issue->mru ? $issue->mru->code . ' - ' . $issue->mru->name : ($issue->mru_id ?: 'N/A') }}</span>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span class="text-slate-500">Billing Cycle:</span>
                    <span class="text-slate-200 font-bold">{{ $issue->billing_month && $issue->billing_year ? $issue->billing_month . '/' . $issue->billing_year : 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Client Diagnostic Context -->
        <div class="bg-slate-950 p-5 rounded-3xl border border-slate-800 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <span>💻</span> Client Device & Browser
            </h3>
            <div class="space-y-2 text-xs font-mono">
                @if(!empty($issue->client_context))
                    <div class="flex items-center justify-between py-1 border-b border-slate-900">
                        <span class="text-slate-500">Screen:</span>
                        <span class="text-slate-200">{{ $issue->client_context['screen'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-900">
                        <span class="text-slate-500">Viewport:</span>
                        <span class="text-slate-200">{{ $issue->client_context['viewport'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-slate-900">
                        <span class="text-slate-500">Timezone:</span>
                        <span class="text-slate-200">{{ $issue->client_context['timezone'] ?? 'N/A' }}</span>
                    </div>
                    <div class="py-1">
                        <span class="text-slate-500 block mb-1">User Agent:</span>
                        <span class="text-[10px] text-slate-400 break-all">{{ $issue->client_context['user_agent'] ?? 'N/A' }}</span>
                    </div>
                @else
                    <div class="text-slate-500 py-4 text-center">No client diagnostic metadata captured.</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal for Marking Resolved -->
    <div x-data="{ openModal: false }"
         @open-resolve-modal.window="openModal = true"
         x-show="openModal"
         x-cloak
         class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="openModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>✅</span> Mark Issue as Resolved
            </h3>
            <p class="text-xs text-slate-400">Describe the fix or code changes applied to resolve this bug.</p>

            <form method="POST" action="{{ route('admin.issues.resolve', $issue) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Resolution Summary <span class="text-rose-500">*</span></label>
                    <textarea name="ai_resolution_notes"
                              rows="4"
                              required
                              placeholder="e.g. Fixed base average locking in SmartAverageCalculationService. Verified with automated tests."
                              class="w-full text-xs bg-slate-950 border border-slate-800 rounded-xl p-3 text-white focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="openModal = false" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl shadow-md transition">Save & Resolve</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
