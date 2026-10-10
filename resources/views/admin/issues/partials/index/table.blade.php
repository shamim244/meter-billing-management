<div class="bg-slate-950 rounded-3xl border border-slate-800 overflow-hidden shadow-xl">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            @include('admin.issues.partials.index.table.thead')
            <tbody class="divide-y divide-slate-800/60 font-medium">
                @forelse($issues as $issue)
                    @include('admin.issues.partials.index.table.row', ['issue' => $issue])
                @empty
                    @include('admin.issues.partials.index.table.empty-state')
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
