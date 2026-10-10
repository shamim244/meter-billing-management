<x-admin-layout :title="'Payment #' . $payment->id . ' Details'">
    <div class="space-y-6">
        @include('admin.payments.nav')
        @include('admin.payments.partials.show.header')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Payment & Billing Agent Details -->
            <div class="lg:col-span-2 space-y-6">
                @include('admin.payments.partials.show.overview')
                @include('admin.payments.partials.show.screenshot')
                @include('admin.payments.partials.show.audit-trail')
            </div>

            <!-- Right Col: Billing Agent & Actions -->
            <div class="space-y-6">
                @include('admin.payments.partials.show.agent-card')
                @include('admin.payments.partials.show.actions')
            </div>
        </div>
    </div>
</x-admin-layout>
