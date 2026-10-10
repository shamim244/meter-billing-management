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
