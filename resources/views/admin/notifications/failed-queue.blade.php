<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>🚨</span> Failed Critical Notifications Queue
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Audit log of critical notifications (e.g. Account Suspensions, Grace Period alerts) that failed email delivery across all 3 retry attempts.
                </p>
            </div>
        </div>

        <!-- Sub-Nav Links -->
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.notifications.email_providers.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                Email Providers
            </a>
            <a href="{{ route('admin.notifications.templates.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                Notification Templates
            </a>
            <a href="{{ route('admin.notifications.failed_queue') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-md shadow-indigo-600/20">
                Failed Critical Queue
            </a>
        </div>

        <!-- Table -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
            <div class="p-4 border-b border-slate-800/80">
                <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider">
                    Failed Critical Delivery Attempts
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-3">Date / Time</th>
                            <th class="py-3 px-3">Recipient Agent</th>
                            <th class="py-3 px-3">Event Type</th>
                            <th class="py-3 px-3">Attempts</th>
                            <th class="py-3 px-3">Last Provider</th>
                            <th class="py-3 px-3">Failure Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300 font-mono text-[11px]">
                        @forelse($failedCriticalDeliveries as $item)
                            <tr class="hover:bg-slate-800/20 transition">
                                <td class="py-3 px-3 text-slate-400">
                                    {{ $item->last_attempted_at ? $item->last_attempted_at->format('Y-m-d H:i') : $item->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="py-3 px-3 font-sans font-semibold text-white">
                                    {{ $item->notification?->user?->name ?? 'Unknown Agent' }}
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $item->notification?->user?->email }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono bg-rose-950/60 border border-rose-800 text-rose-300">
                                        {{ $item->notification?->event_type }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center text-rose-400 font-bold">
                                    {{ $item->attempt_count }} / 3
                                </td>
                                <td class="py-3 px-3 font-sans">
                                    {{ $item->emailProviderInstance?->label ?? 'Exhausted Chain' }}
                                </td>
                                <td class="py-3 px-3 text-rose-400 truncate max-w-[280px]" title="{{ $item->failed_reason }}">
                                    {{ $item->failed_reason }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500 font-sans">
                                    ✨ No critical notification delivery failures recorded. All critical notifications are delivering cleanly.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($failedCriticalDeliveries->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $failedCriticalDeliveries->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
