/**
 * Subscription Purchase & Checkout Alpine.js Application
 * Handles Razorpay & Cashfree SDK integration for direct plan purchase
 */
function subscriptionPurchaseApp(config) {
    config = config || {};

    return {
        mode: config.mode || 'pg',
        amount: config.amount || 0,
        activePgDriver: config.activePgDriver || 'razorpay',
        businessUpiId: config.businessUpiId || '',
        businessUpiName: config.businessUpiName || '',
        planId: config.planId || '',
        planName: config.planName || '',
        durationMonths: config.durationMonths || 1,
        actionMode: config.actionMode || '',
        processUrl: config.processUrl || '',
        verifyUrl: config.verifyUrl || '',
        indexUrl: config.indexUrl || '',
        csrfToken: config.csrfToken || '',

        utrNumber: '',
        bankReference: '',
        copiedUpi: false,
        copiedBank: false,
        isSubmitting: false,
        errorMessage: null,

        copyText(text, type) {
            if (!text) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            }
            if (type === 'upi') {
                this.copiedUpi = true;
                setTimeout(() => {
                    this.copiedUpi = false;
                }, 2000);
            } else if (type === 'bank') {
                this.copiedBank = true;
                setTimeout(() => {
                    this.copiedBank = false;
                }, 2000);
            }
        },

        get qrCodeUrl() {
            const upiId = this.businessUpiId;
            const name = encodeURIComponent(this.businessUpiName);
            const am = this.amount;
            const upiString = `upi://pay?pa=${upiId}&pn=${name}&am=${am}&cu=INR&tn=NBPDCL_Subscription_${this.planId}`;
            return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(upiString)}`;
        },

        async handleCheckout(e) {
            if (this.mode !== 'pg') {
                this.isSubmitting = true;
                return true;
            }

            e.preventDefault();
            this.isSubmitting = true;
            this.errorMessage = null;

            try {
                const response = await fetch(this.processUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        mode: 'pg',
                        action_mode: this.actionMode
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Failed to initialize payment gateway checkout.');
                }

                // 1. Razorpay Checkout Flow
                if (data.order && data.order.gateway === 'razorpay') {
                    const _this = this;
                    const options = {
                        key: data.order.key,
                        amount: data.order.amount_paise,
                        currency: 'INR',
                        name: data.order.name || 'NBPDCL SaaS Billing',
                        description: `Subscription: ${this.planName} (${this.durationMonths} mo)`,
                        order_id: data.order.order_id,
                        prefill: {
                            name: data.order.customer_name,
                            email: data.order.customer_email,
                            contact: data.order.customer_phone || ''
                        },
                        notes: data.order.notes || {},
                        theme: data.order.theme || { color: '#4f46e5' },
                        handler: function (resp) {
                            window.location.href = `${_this.verifyUrl}?razorpay_payment_id=${encodeURIComponent(resp.razorpay_payment_id)}&razorpay_order_id=${encodeURIComponent(resp.razorpay_order_id)}&razorpay_signature=${encodeURIComponent(resp.razorpay_signature)}`;
                        },
                        modal: {
                            ondismiss: function () {
                                _this.isSubmitting = false;
                            }
                        }
                    };

                    if (window.Razorpay) {
                        const rzp = new window.Razorpay(options);
                        rzp.on('payment.failed', function (resp) {
                            _this.isSubmitting = false;
                            _this.errorMessage = (resp.error && resp.error.description) ? resp.error.description : 'Payment was declined.';
                        });
                        rzp.open();
                    } else {
                        throw new Error('Razorpay checkout library failed to load.');
                    }
                    return;
                }

                // 2. Cashfree Drop Checkout Flow
                if (data.order && data.order.gateway === 'cashfree') {
                    if (window.Cashfree) {
                        const cashfree = window.Cashfree({ mode: data.order.environment === 'production' ? 'production' : 'sandbox' });
                        cashfree.checkout({
                            paymentSessionId: data.order.payment_session_id,
                            redirectTarget: '_self'
                        });
                    } else {
                        window.location.href = this.indexUrl;
                    }
                    return;
                }

                window.location.href = this.indexUrl;
            } catch (err) {
                this.isSubmitting = false;
                this.errorMessage = err.message || 'Payment initiation failed. Please try again.';
            }
        }
    };
}

window.subscriptionPurchaseApp = subscriptionPurchaseApp;
