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
