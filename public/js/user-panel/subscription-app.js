/**
 * Subscription & Storage Allocation Client Application
 * Decoupled Alpine.js component for subscription checkout, quotes, coupons & wallet payments.
 */
function subscriptionApp() {
    const config = window.subscriptionConfig || {};

    return {
        showModal: false,
        isLoadingQuote: false,
        quote: null,
        selectedPlan: null,
        selectedDuration: null,
        selectedActionMode: 'extend',
        currentStep: 1,
        hasActiveSubscription: !!config.hasActiveSubscription,
        walletBalance: parseFloat(config.walletBalance) || 0,
        isProcessingWallet: false,
        walletError: null,
        mruConflict: false,
        activeMrus: [],
        excessMrus: 0,
        newPlanQuota: 0,
        isLockingMru: false,
        lockSuccessMsg: null,
        showReceiptModal: false,
        receiptData: null,

        couponCodeInput: '',
        appliedCoupon: null,
        couponError: null,
        isValidatingCoupon: false,

        get availableDurations() {
            return (this.selectedPlan && this.selectedPlan.durations) ? this.selectedPlan.durations : [];
        },

        get isSamePlan() {
            const currentPlanId = config.currentPlanId;
            return !!(currentPlanId && this.selectedPlan && currentPlanId === this.selectedPlan.id);
        },

        get selectedDurationPrice() {
            if (this.quote && this.quote.action_type === 'upgrade') {
                return parseFloat(this.quote.final_amount) || 0;
            }
            if (this.quote && this.quote.action_type === 'downgrade') {
                return 0;
            }
            return this.selectedDuration ? (parseFloat(this.selectedDuration.final_price) || 0) : 0;
        },

        get directPurchaseUrl() {
            if (!this.selectedPlan || !this.selectedDuration) return '#';
            const couponParam = this.appliedCoupon ? `&coupon_code=${encodeURIComponent(this.appliedCoupon.code)}` : '';
            return `/subscription/purchase/${this.selectedPlan.id}/${this.selectedDuration.id}?action_mode=${this.selectedActionMode}${couponParam}`;
        },

        async openCheckoutModal(plan, duration) {
            this.selectedPlan = plan;
            this.selectedDuration = duration || (plan.durations && plan.durations.length ? plan.durations[0] : null);
            this.walletError = null;
            this.couponError = null;
            this.couponCodeInput = '';
            this.appliedCoupon = null;
            this.mruConflict = false;
            this.activeMrus = [];
            this.excessMrus = 0;
            this.newPlanQuota = 0;
            this.lockSuccessMsg = null;
            this.isProcessingWallet = false;
            this.quote = null;
            this.isLoadingQuote = true;
            this.showModal = true;

            const currentPlanId = config.currentPlanId;
            if (this.hasActiveSubscription && currentPlanId && currentPlanId === plan.id) {
                this.selectedActionMode = 'extend';
                this.currentStep = 1;
            } else if (this.hasActiveSubscription) {
                this.selectedActionMode = 'shift';
                this.currentStep = 1;
            } else {
                this.selectedActionMode = 'new';
                this.currentStep = 1;
            }

            await this.fetchQuote(this.selectedActionMode);
        },

        async selectDuration(duration) {
            if (this.selectedDuration && this.selectedDuration.id === duration.id) return;
            this.selectedDuration = duration;
            await this.fetchQuote(this.selectedActionMode);
        },

        async switchActionMode(mode) {
            if (this.selectedActionMode === mode) return;
            this.selectedActionMode = mode;
            await this.fetchQuote(mode);
        },

        goToStep(step) {
            if (step === 3 && this.mruConflict) return;
            this.currentStep = step;
        },

        async fetchQuote(mode) {
            if (!this.selectedPlan || !this.selectedDuration) return;
            this.isLoadingQuote = true;
            this.walletError = null;
            const couponParam = this.couponCodeInput.trim() ? `&coupon_code=${encodeURIComponent(this.couponCodeInput.trim())}` : '';
            try {
                const res = await fetch(`/subscription/quote/${this.selectedPlan.id}/${this.selectedDuration.id}?action_mode=${mode}${couponParam}`, {
                    headers: { 'Accept': 'application/json' }
                });
                let data;
                const resText = await res.text();
                try {
                    data = JSON.parse(resText);
                } catch (parseErr) {
                    this.walletError = `Server returned an unexpected response (Status ${res.status}).`;
                    return;
                }

                if (data.success) {
                    this.quote = data;
                    if (data.coupon && data.coupon.valid) {
                        this.appliedCoupon = data.coupon;
                    }
                    if (data.action_type === 'downgrade' && data.downgrade_eligibility && !data.downgrade_eligibility.eligible) {
                        this.mruConflict = true;
                        this.activeMrus = data.downgrade_eligibility.active_mrus || [];
                        this.excessMrus = data.downgrade_eligibility.excess_mrus || 0;
                        this.newPlanQuota = data.downgrade_eligibility.new_plan_quota || 0;
                    } else {
                        this.mruConflict = false;
                    }
                } else {
                    this.walletError = data.message || 'Failed to calculate quote.';
                }
            } catch (err) {
                this.walletError = err.message || 'Failed to load plan quote.';
            } finally {
                this.isLoadingQuote = false;
            }
        },

        async applyCoupon() {
            if (!this.couponCodeInput.trim()) return;
            this.isValidatingCoupon = true;
            this.couponError = null;
            try {
                const res = await fetch(`/subscription/quote/${this.selectedPlan.id}/${this.selectedDuration.id}?action_mode=${this.selectedActionMode}&coupon_code=${encodeURIComponent(this.couponCodeInput.trim())}`, {
                    headers: { 'Accept': 'application/json' }
                });
                let data;
                const resText = await res.text();
                try {
                    data = JSON.parse(resText);
                } catch (parseErr) {
                    this.couponError = `Server returned an unexpected response (Status ${res.status}).`;
                    return;
                }

                if (data.success) {
                    this.quote = data;
                    if (data.coupon && data.coupon.valid) {
                        this.appliedCoupon = data.coupon;
                        this.couponError = null;
                    } else if (data.coupon && !data.coupon.valid) {
                        this.couponError = data.coupon.message;
                        this.appliedCoupon = null;
                    }
                } else {
                    this.couponError = data.message || 'Invalid coupon code.';
                    this.appliedCoupon = null;
                }
            } catch (err) {
                this.couponError = err.message || 'Failed to validate coupon code.';
            } finally {
                this.isValidatingCoupon = false;
            }
        },

        async removeCoupon() {
            this.couponCodeInput = '';
            this.appliedCoupon = null;
            this.couponError = null;
            await this.fetchQuote(this.selectedActionMode);
        },

        async confirmWalletPayment() {
            if (!this.selectedPlan || !this.selectedDuration) return;
            this.isProcessingWallet = true;
            this.walletError = null;
            this.lockSuccessMsg = null;

            try {
                const endpoint = config.subscribeWalletUrl || '/subscription/subscribe-wallet';
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        plan_id: this.selectedPlan.id,
                        duration_id: this.selectedDuration.id,
                        action_mode: this.selectedActionMode,
                        coupon_code: this.appliedCoupon ? this.appliedCoupon.code : (this.couponCodeInput.trim() || null)
                    })
                });

                let data;
                const responseText = await response.text();
                try {
                    data = JSON.parse(responseText);
                } catch (jsonErr) {
                    throw new Error(`Server returned an unexpected response (Status ${response.status}). Please try again or contact support.`);
                }

                if (!response.ok || !data.success) {
                    if (data.ineligible_mrus) {
                        this.mruConflict = true;
                        this.activeMrus = data.active_mrus || [];
                        this.excessMrus = data.excess_mrus || 0;
                        this.newPlanQuota = data.new_plan_quota || 0;
                    }
                    throw new Error(data.message || 'Wallet payment failed.');
                }

                this.receiptData = {
                    planName: this.selectedPlan.name,
                    actionType: this.quote?.action_type || (this.selectedActionMode === 'extend' ? 'extend' : 'shift'),
                    message: data.message,
                    amountPaid: this.quote?.final_amount || 0,
                    amountCredited: this.quote?.prorated_credit || 0,
                    duration: this.selectedDuration ? (this.selectedDuration.formatted_duration || this.selectedDuration.name || ((this.selectedDuration.duration_value || this.selectedDuration.duration_months) + (this.selectedDuration.duration_unit === 'day' ? ' Days' : ' Month(s)'))) : '',
                };
                this.showModal = false;
                this.showReceiptModal = true;
            } catch (err) {
                this.walletError = err.message;
            } finally {
                this.isProcessingWallet = false;
            }
        },

        async lockMruFromModal(mruId) {
            this.isLockingMru = true;
            this.lockSuccessMsg = null;
            try {
                const res = await fetch('/mrus/' + mruId + '/lock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({ reason: 'plan_downgrade' })
                });
                let d;
                const resText = await res.text();
                try {
                    d = JSON.parse(resText);
                } catch (parseErr) {
                    throw new Error(`Failed to lock MRU (Status ${res.status}).`);
                }

                if (d.success) {
                    this.activeMrus = this.activeMrus.filter(m => m.id !== mruId);
                    this.excessMrus = Math.max(0, this.excessMrus - 1);
                    this.lockSuccessMsg = d.message;
                    if (this.excessMrus <= 0) {
                        this.mruConflict = false;
                        this.walletError = null;
                    }
                } else {
                    this.walletError = d.message || 'Failed to lock MRU.';
                }
            } catch (e) {
                this.walletError = e.message;
            } finally {
                this.isLockingMru = false;
            }
        }
    };
}
