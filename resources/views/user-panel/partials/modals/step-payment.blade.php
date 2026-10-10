{{-- Step 3: Payment, Confirmation & Coupon Checkout --}}
<div x-show="currentStep === 3" class="space-y-4">
    @include('user-panel.partials.modals.payment.downgrade-refund')

    <!-- Normal Payment Theme (Upgrade, Renewal, or New) -->
    <template x-if="quote.action_type !== 'downgrade'">
        <div class="space-y-3">
            @include('user-panel.partials.modals.payment.payable-coupon')
            @include('user-panel.partials.modals.payment.payment-options')
        </div>
    </template>

    @include('user-panel.partials.modals.payment.back-button')
</div>
