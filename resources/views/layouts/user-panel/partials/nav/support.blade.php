<!-- Section 4: Support & Feedback -->
<div class="space-y-1">
    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">
        Support & System
    </div>

    <a href="{{ route('user-panel.issues') }}" 
       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user-panel.issues') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
        <span class="text-base">🐞</span>
        <div class="flex-1 flex items-center justify-between">
            <span>My Bug Reports</span>
            @php
                $openIssuesCount = Auth::user()->issueReports()->whereIn('status', ['pending', 'verified', 'in_progress'])->count();
            @endphp
            @if($openIssuesCount > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-mono font-bold bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                    {{ $openIssuesCount }} active
                </span>
            @endif
        </div>
    </a>
</div>
