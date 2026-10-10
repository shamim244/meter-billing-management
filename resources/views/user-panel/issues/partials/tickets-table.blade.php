<!-- Tickets List Table / Card Stream -->
<div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden">
    @if($issues->isEmpty())
        @include('user-panel.issues.partials.tickets-table.empty-state')
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                @include('user-panel.issues.partials.tickets-table.thead')
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @foreach($issues as $issue)
                        @include('user-panel.issues.partials.tickets-table.row', ['issue' => $issue])
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($issues->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40">
                {{ $issues->links() }}
            </div>
        @endif
    @endif
</div>
