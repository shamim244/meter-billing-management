<!-- Durations Table Card -->
<div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
    @include('admin.plans.partials.durations.table.header')

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            @include('admin.plans.partials.durations.table.thead')
            <tbody class="divide-y divide-slate-800/60">
                @forelse($plan->durations as $dur)
                    @include('admin.plans.partials.durations.table.row', ['dur' => $dur, 'plan' => $plan])
                @empty
                    @include('admin.plans.partials.durations.table.empty-state')
                @endforelse
            </tbody>
        </table>
    </div>
</div>
