{{-- Backups Archives Ledger Table --}}
<div class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-xl space-y-4">
    @include('admin.backups.partials.archives.header-filters')

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            @include('admin.backups.partials.archives.thead')
            <tbody class="divide-y divide-slate-800 font-medium">
                @forelse($backups as $b)
                    @include('admin.backups.partials.archives.row', ['b' => $b])
                @empty
                    @include('admin.backups.partials.archives.empty-state')
                @endforelse
            </tbody>
        </table>
    </div>

    @if($backups->hasPages())
        <div class="pt-4 border-t border-slate-800">
            {{ $backups->links() }}
        </div>
    @endif
</div>
