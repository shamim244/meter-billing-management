/**
 * Admin Payments Sandbox Simulator
 * Decoupled Alpine.js Component
 */

function paymentSimulatorApp(config) {
    const c = config || window.paymentSimulatorConfig || {};

    return {
        webhookGateway: c.webhookGateway || 'razorpay',
        webhookEvent: c.webhookEvent || 'payment.captured',
        webhookAmount: Number(c.webhookAmount) || 1500,
        webhookRunning: false,
        webhookResult: null,
        webhookUrl: c.webhookUrl || '',
        csrfToken: c.csrfToken || '',

        onGatewayChange() {
            this.webhookEvent = (this.webhookGateway === 'razorpay' ? 'payment.captured' : 'PAYMENT_SUCCESS_WEBHOOK');
        },

        async triggerWebhook() {
            this.webhookRunning = true;
            this.webhookResult = null;
            try {
                const res = await fetch(this.webhookUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        gateway: this.webhookGateway,
                        event_type: this.webhookEvent,
                        amount: this.webhookAmount
                    })
                });
                const data = await res.json();
                this.webhookResult = data;
            } catch (err) {
                this.webhookResult = { error: err.message || 'Webhook simulation failed.' };
            } finally {
                this.webhookRunning = false;
            }
        }
    };
}

if (typeof window !== 'undefined') {
    window.paymentSimulatorApp = paymentSimulatorApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('paymentSimulatorApp', (config) => paymentSimulatorApp(config));
        }
    });
}
