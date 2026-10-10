<div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            @include('admin.users.partials.index.table.thead')
            <tbody class="divide-y divide-slate-800/70 font-medium">
                @forelse($users as $user)
                    @include('admin.users.partials.index.table.row', ['user' => $user])
                @empty
                    @include('admin.users.partials.index.table.empty-state')
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/60">
            {{ $users->links() }}
        </div>
    @endif
</div>
