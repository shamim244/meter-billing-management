<!-- Active API Keys Table -->
<div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xl overflow-hidden">
    @include('user-panel.api-keys.partials.table.header')

    @if($apiKeys->isEmpty())
        @include('user-panel.api-keys.partials.table.empty-state')
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                @include('user-panel.api-keys.partials.table.thead')
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @foreach($apiKeys as $key)
                        @include('user-panel.api-keys.partials.table.row', ['key' => $key])
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
