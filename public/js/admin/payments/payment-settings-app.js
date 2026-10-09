function paymentSettingsApp(config) {
    const c = config || window.paymentSettingsConfig || {};
    return {
        pgEnabled: Boolean(c.pgEnabled),
        cashfreeEnabled: Boolean(c.cashfreeEnabled),
        razorpayEnabled: Boolean(c.razorpayEnabled),
        manualUpiEnabled: Boolean(c.manualUpiEnabled),
        bankTransferEnabled: Boolean(c.bankTransferEnabled),
        activePgDriver: c.activePgDriver || 'razorpay'
    };
}

if (typeof window !== 'undefined') {
    window.paymentSettingsApp = paymentSettingsApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('paymentSettingsApp', (config) => paymentSettingsApp(config));
        }
    });
}
