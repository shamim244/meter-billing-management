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
