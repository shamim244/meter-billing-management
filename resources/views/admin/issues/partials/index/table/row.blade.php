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
