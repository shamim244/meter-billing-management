{{-- Available Subscription Plans Grid --}}
<div>
    @include('user-panel.partials.plans-grid.header')

    @if($plans->isEmpty())
        @include('user-panel.partials.plans-grid.empty-state')
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($plans as $plan)
                @include('user-panel.partials.plans-grid.card', ['plan' => $plan, 'activeSubscription' => $activeSubscription])
            @endforeach
        </div>
    @endif
</div>
