/**
 * Payment Gateway & Wallet Top-up Checkout Alpine.js Application
 * Handles Razorpay & Cashfree SDK integration, Coupon Validation, Manual UPI & Bank Transfers
 */
function paymentCheckoutApp(config) {
    config = config || {};

    return {
        mode: config.mode || 'pg',
        purpose: config.purpose || 'wallet_topup',
        amount: config.amount || 500,
        minAmount: config.minAmount || 100,
        activePgDriver: config.activePgDriver || 'razorpay',
        businessUpiId: config.businessUpiId || '',
        businessUpiName: config.businessUpiName || '',
        utrNumber: '',
        bankReference: '',
        copiedUpi: false,
        copiedBank: false,
        isSubmitting: false,
        errorMessage: null,
        couponCodeInput: '',
        appliedCoupon: null,
        couponError: null,
        isValidatingCoupon: false,

        setAmount(val) {
            this.amount = val;
            if (this.appliedCoupon) {
                this.validateCoupon();
            }
        },

        async validateCoupon() {
            if (!this.couponCodeInput.trim()) return;
            this.isValidatingCoupon = true;
            this.couponError = null;

            try {
                const response = await fetch(config.couponValidationUrl || '/payments/validate-coupon', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken || ''
                    },
                    body: JSON.stringify({
                        code: this.couponCodeInput.trim(),
                        amount: this.amount,
                        action_type: 'topup_bonus'
                    })
                });

                const data = await response.json();
                if (data.valid) {
                    this.appliedCoupon = data;
                    this.couponError = null;
                } else {
                    this.couponError = data.message || 'Invalid coupon code for this recharge amount.';
                    this.appliedCoupon = null;
                }
            } catch (err) {
                this.couponError = 'Failed to validate coupon code.';
            } finally {
                this.isValidatingCoupon = false;
            }
        },

        removeCoupon() {
            this.couponCodeInput = '';
            this.appliedCoupon = null;
            this.couponError = null;
        },

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
            const am = this.amount || this.minAmount;
            const upiString = `upi://pay?pa=${upiId}&pn=${name}&am=${am}&cu=INR&tn=NBPDCL_Wallet_Topup`;
            return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(upiString)}`;
        },

        async handleCheckout(e) {
            if (this.mode !== 'pg') {
                this.isSubmitting = true;
                return true; // standard multi-part submit for manual uploads
            }

            e.preventDefault();
            this.isSubmitting = true;
            this.errorMessage = null;

            try {
                const response = await fetch(config.paymentStoreUrl || '/payments/store', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken || ''
                    },
                    body: JSON.stringify({
                        mode: 'pg',
                        purpose: 'wallet_topup',
                        amount: this.amount,
                        coupon_code: this.appliedCoupon ? this.appliedCoupon.code : null
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
                        description: data.order.description || 'Wallet Top-up',
                        order_id: data.order.order_id,
                        prefill: {
                            name: data.order.customer_name,
                            email: data.order.customer_email,
                            contact: data.order.customer_phone || ''
                        },
                        notes: data.order.notes || {},
                        theme: data.order.theme || { color: '#4f46e5' },
                        handler: function (resp) {
                            const verifyBase = config.paymentVerifyUrl || '/payments/verify';
                            window.location.href = `${verifyBase}?razorpay_payment_id=${encodeURIComponent(resp.razorpay_payment_id)}&razorpay_order_id=${encodeURIComponent(resp.razorpay_order_id)}&razorpay_signature=${encodeURIComponent(resp.razorpay_signature)}`;
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
                        window.location.href = config.paymentsIndexUrl || '/payments';
                    }
                    return;
                }

                window.location.href = config.paymentsIndexUrl || '/payments';
            } catch (err) {
                this.isSubmitting = false;
                this.errorMessage = err.message || 'Payment initiation failed. Please try again.';
            }
        }
    };
}

window.paymentCheckoutApp = paymentCheckoutApp;
