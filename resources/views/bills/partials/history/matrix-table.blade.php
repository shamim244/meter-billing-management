{{-- 📊 Dedicated Monthly Meter Reading History (2D Matrix: Official PDF vs Field Working Reading) --}}
<div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
    @include('bills.partials.history.matrix.stats-header')

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
            @include('bills.partials.history.matrix.thead')
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                @forelse($meterMatrix['periods'] ?? [] as $period)
                    @include('bills.partials.history.matrix.row', ['period' => $period])
                @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-slate-400 dark:text-slate-600 text-xs">
                            No dual-source meter readings recorded yet. Once bills are parsed or working readings are entered, they will appear here.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
