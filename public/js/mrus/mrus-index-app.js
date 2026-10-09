/**
 * MRUs Workspace Index App Logic
 * Decoupled client-side Alpine component for MRU management
 */
function mrusIndexApp() {
    const config = window.mrusIndexConfig || {};

    return {
        showCreateModal: false,
        showCycleModal: false,
        showExistingMruPopup: false,
        showDeleteMruModal: false,
        isDeletingMru: false,
        targetMru: null,
        existingMruData: null,
        searchQuery: '',
        statusFilter: 'all',
        mruList: config.mrus || [],
        selectedMruId: config.selectedMruId || '',
        cycleMonth: config.cycleMonth || (new Date().getMonth() + 1),
        cycleYear: config.cycleYear || new Date().getFullYear(),
        executingAction: 'download_all',
        cycleInProgress: false,
        cycleResult: null,

        // Create MRU Form State
        newMruCode: '',
        newMruName: '',
        newMruIdentifier: '',
        isSubmittingMru: false,
        createMruError: null,
        mruOverageRequired: false,
        mruOverageAmount: 0,
        mruOverageWalletBalance: 0,
        mruOverageInsufficient: false,
        mruOverageMessage: '',
        mruTopupUrl: config.walletIndexUrl || '/wallet',
        mruUpgradeUrl: config.subscriptionUrl || '/user-panel/subscription',

        // Cycle Overage State
        cycleOverageRequired: false,
        cycleOverageAmount: 0,
        cycleOverageWalletBalance: 0,
        cycleOverageInsufficient: false,
        cycleOverageMessage: '',
        cycleTopupUrl: config.walletIndexUrl || '/wallet',
        cycleUpgradeUrl: config.subscriptionUrl || '/user-panel/subscription',

        get selectedMru() {
            return this.mruList.find(m => String(m.id) === String(this.selectedMruId)) || null;
        },

        get selectedMruHasNoConsumers() {
            return this.selectedMru && Number(this.selectedMru.consumer_accounts_count) === 0;
        },

        get filteredMrus() {
            return this.mruList.filter(m => {
                const matchesSearch = !this.searchQuery || 
                    (m.name && m.name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (m.code && m.code.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (m.full_identifier && m.full_identifier.toLowerCase().includes(this.searchQuery.toLowerCase()));

                const matchesStatus = this.statusFilter === 'all' || m.status === this.statusFilter;

                return matchesSearch && matchesStatus;
            });
        },

        openCreateModal() {
            this.newMruCode = '';
            this.newMruName = '';
            this.newMruIdentifier = '';
            this.createMruError = null;
            this.mruOverageRequired = false;
            this.mruOverageAmount = 0;
            this.mruOverageWalletBalance = 0;
            this.mruOverageInsufficient = false;
            this.mruOverageMessage = '';
            this.mruTopupUrl = config.walletIndexUrl || '/wallet';
            this.mruUpgradeUrl = config.subscriptionUrl || '/user-panel/subscription';
            this.showCreateModal = true;
        },

        submitCreateMru(payOverage = false) {
            if (!this.newMruCode.trim() || !this.newMruName.trim()) return;

            this.isSubmittingMru = true;
            this.createMruError = null;

            fetch('/mrus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    code: this.newMruCode,
                    name: this.newMruName,
                    full_identifier: this.newMruIdentifier,
                    pay_overage: payOverage ? 1 : 0
                })
            })
            .then(async res => {
                const data = await res.json();
                if (res.status === 402 && data.requires_overage) {
                    this.mruOverageRequired = true;
                    this.mruOverageAmount = data.amount_due || 0;
                    this.mruOverageWalletBalance = data.wallet_balance ?? 0;
                    this.mruOverageInsufficient = !!data.is_insufficient_balance || (this.mruOverageWalletBalance < this.mruOverageAmount);
                    this.mruOverageMessage = data.message || 'Plan MRU limit exceeded. Wallet deduction required.';
                    this.mruTopupUrl = data.topup_url || config.walletIndexUrl || '/wallet';
                    this.mruUpgradeUrl = data.upgrade_url || config.subscriptionUrl || '/user-panel/subscription';
                    return null;
                }
                if (!res.ok) {
                    if (data.requires_subscription && data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return null;
                    }
                    throw new Error(data.message || 'Server error occurred');
                }
                return data;
            })
            .then(data => {
                if (!data) return;
                if (data.already_exists) {
                    this.showCreateModal = false;
                    this.existingMruData = data.mru;
                    this.showExistingMruPopup = true;
                } else if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    window.location.reload();
                }
            })
            .catch(err => {
                this.createMruError = err.message || 'Failed to process MRU workspace.';
            })
            .finally(() => {
                this.isSubmittingMru = false;
            });
        },

        openCycleModal(mruId = null) {
            if (mruId) {
                this.selectedMruId = mruId;
            } else if (!this.selectedMruId && this.mruList.length > 0) {
                this.selectedMruId = this.mruList[0].id;
            }
            this.cycleResult = null;
            this.cycleOverageRequired = false;
            this.cycleOverageAmount = 0;
            this.cycleOverageWalletBalance = 0;
            this.cycleOverageInsufficient = false;
            this.cycleOverageMessage = '';
            this.cycleTopupUrl = config.walletIndexUrl || '/wallet';
            this.cycleUpgradeUrl = config.subscriptionUrl || '/user-panel/subscription';
            this.showCycleModal = true;
        },

        launchBillingCycle(actionType = 'download_all', payOverage = false) {
            if (!this.selectedMruId) return;

            this.executingAction = actionType;
            this.cycleInProgress = true;
            this.cycleResult = null;

            fetch('/mrus/billing-cycle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || ''
                },
                body: JSON.stringify({
                    mru_id: this.selectedMruId,
                    billing_month: this.cycleMonth,
                    billing_year: this.cycleYear,
                    action_type: actionType,
                    pay_overage: payOverage ? 1 : 0
                })
            })
            .then(async res => {
                const data = await res.json();
                if (res.status === 402 && data.requires_overage) {
                    this.cycleOverageRequired = true;
                    this.cycleOverageAmount = data.amount_due || 0;
                    this.cycleOverageWalletBalance = data.wallet_balance ?? 0;
                    this.cycleOverageInsufficient = !!data.is_insufficient_balance || (this.cycleOverageWalletBalance < this.cycleOverageAmount);
                    this.cycleOverageMessage = data.message || 'Consumer quota exceeded. Wallet deduction required.';
                    this.cycleTopupUrl = data.topup_url || config.walletIndexUrl || '/wallet';
                    this.cycleUpgradeUrl = data.upgrade_url || config.subscriptionUrl || '/user-panel/subscription';
                    return null;
                }
                if (!res.ok) {
                    if (data.requires_subscription) {
                        this.cycleResult = data;
                        return null;
                    }
                    throw new Error(data.message || 'Server returned an error');
                }
                return data;
            })
            .then(json => {
                if (!json) return;
                this.cycleResult = json;
                if (json.success) {
                    setTimeout(() => {
                        if (json.redirect_url) {
                            window.location.href = json.redirect_url;
                        } else {
                            window.location.reload();
                        }
                    }, 800);
                }
            })
            .catch(err => {
                this.cycleResult = {
                    success: false,
                    message: err.message || 'An error occurred while executing billing cycle.'
                };
            })
            .finally(() => {
                this.cycleInProgress = false;
            });
        },

        openDeleteMruModal(mru) {
            this.targetMru = mru;
            this.showDeleteMruModal = true;
        },

        confirmDeleteMru() {
            if (!this.targetMru) return;
            this.isDeletingMru = true;

            fetch(`/mrus/${this.targetMru.id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    'Accept': 'application/json'
                }
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Error deleting MRU');
                return data;
            })
            .then(data => {
                this.isDeletingMru = false;
                this.showDeleteMruModal = false;
                window.location.reload();
            })
            .catch(err => {
                this.isDeletingMru = false;
                alert('Failed to delete MRU: ' + err.message);
            });
        },

        lockMru(mru) {
            if (!confirm(`Are you sure you want to lock '${mru.name} (${mru.code})'?\n\nLocking this MRU frees up plan quota so you can downgrade or create new MRUs. You can unlock it anytime.`)) return;

            fetch(`/mrus/${mru.id}/lock`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reason: 'user_manual_lock' })
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to lock MRU');
                return data;
            })
            .then(data => {
                window.location.reload();
            })
            .catch(err => {
                alert('Error locking MRU: ' + err.message);
            });
        },

        unlockMru(mru) {
            if (!confirm(`Unlock '${mru.name} (${mru.code})'?`)) return;

            fetch(`/mrus/${mru.id}/unlock`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ pay_overage: true })
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to unlock MRU');
                return data;
            })
            .then(data => {
                window.location.reload();
            })
            .catch(err => {
                alert('Error unlocking MRU: ' + err.message);
            });
        }
    };
}
