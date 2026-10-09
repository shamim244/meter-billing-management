function dashboardApp() {
    const cfg = window.dashboardConfig || {};
    return {
                loading: true,
                items: [],
                pagination: {},
                viewMode: localStorage.getItem('dashboard_view_mode') || 'table',
                currentCardIndex: 0,
                loadingMoreCards: false,
                touchStartX: 0,
                touchStartY: 0,
                isPinching: false,
                copiedCaId: null,
                copiedCaTimeout: null,

                // Offline & Reconnection Sync Engine (Lifetime Future-Proof)
                isOnline: navigator.onLine,
                isServerReachable: true,
                isSyncing: false,
                isCheckingConnection: false,
                lastSyncedAt: null,
                offlineQueue: (function() {
                    try {
                        return JSON.parse(localStorage.getItem('nbpdcl_offline_queue_v1') || '[]');
                    } catch (e) {
                        return [];
                    }
                })(),
                pendingCaSet: {},
                inFlightControllers: {},

                abortInFlight(key) {
                    if (this.inFlightControllers && this.inFlightControllers[key]) {
                        try {
                            this.inFlightControllers[key].abort();
                        } catch (e) {}
                        delete this.inFlightControllers[key];
                    }
                },
                syncErrors: [],

                jumpToCa(caNumber) {
                    if (!caNumber) return;
                    const idx = this.items.findIndex(b => String(b.ca_number) === String(caNumber));
                    if (idx !== -1) {
                        if (this.viewMode === 'card') {
                            this.currentCardIndex = idx;
                        } else {
                            const row = document.getElementById(`row-${caNumber}`);
                            if (row) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    } else {
                        this.searchQuery = String(caNumber);
                        this.fetchData(1);
                    }
                },

                // Modals
                showCreateMruModal: false,
                showExistingMruPopup: false,
                existingMruData: null,
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
                mruTopupUrl: cfg.mruTopupUrl || '/wallet',
                mruUpgradeUrl: cfg.mruUpgradeUrl || '/user-panel/subscription',
                showNewCycleModal: false,
                showPdfViewerModal: false,
                activePdfBill: null,
                showQuickPullModal: false,
                quickPullCa: '',
                quickPullLoading: false,
                quickPullResult: null,
                syncingSingle: null,
                syncingMissing: false,

                // Shortcuts State
                showShortcutsModal: false,
                rebindingAction: null,
                rebindDisplay: '',
                rebindSession: null,
                shortcuts: cfg.shortcuts || {},
                shortcutLabels: cfg.shortcutLabels || {},
                cardDensity: cfg.cardDensity || 'compact',
                amountSize: cfg.amountSize || 'standard',
                showRemarkPresets: !!cfg.showRemarkPresets,
                colorSettings: cfg.colorSettings || { enabled: true, amount_safe_ceiling: 500, amount_warning_ceiling: 1500, amount_danger_floor: 2500, units_safe_ceiling: 50, units_warning_ceiling: 120, units_danger_floor: 200 },

                cycleMonth: cfg.selectedMonth || (new Date().getMonth() + 1),
                cycleYear: cfg.selectedYear || new Date().getFullYear(),
                executingAction: 'download_all',
                cycleInProgress: false,
                cycleResult: null,

                // Toast with Undo
                toast: {
                    show: false,
                    message: '',
                    icon: '✅',
                    undoData: null,
                    timer: null
                },

                // Filters & Sorting (State Persistence & Auto-Validation)
                mruPeriodsMap: cfg.mruPeriodsMap || {},
                filterMru: (function() {
                    const map = cfg.mruPeriodsMap || {};
                    const stored = localStorage.getItem('dashboard_mru');
                    if (stored && map[String(stored)]) return String(stored);
                    return String(cfg.selectedMruId || '');
                })(),
                availablePeriods: (function() {
                    const map = cfg.mruPeriodsMap || {};
                    const stored = localStorage.getItem('dashboard_mru');
                    const mruKey = (stored && map[String(stored)]) ? String(stored) : String(cfg.selectedMruId || '');
                    return map[mruKey] || [];
                })(),
                selectedPeriodKey: (function() {
                    const map = cfg.mruPeriodsMap || {};
                    const storedMru = localStorage.getItem('dashboard_mru');
                    const mruKey = (storedMru && map[String(storedMru)]) ? String(storedMru) : String(cfg.selectedMruId || '');
                    const list = map[mruKey] || [];
                    const storedPeriod = localStorage.getItem('dashboard_period');
                    if (storedPeriod && list.some(p => p.key === storedPeriod)) return storedPeriod;
                    if (list.length > 0) return list[0].key;
                    return String(cfg.selectedMonth || '') + '_' + String(cfg.selectedYear || '');
                })(),
                selectedMonth: cfg.selectedMonth,
                selectedYear: cfg.selectedYear,
                filterStatus: 'all',
                basisFilter: localStorage.getItem('dashboard_basis_filter') || 'all',
                tagFilter: 'all',
                availableTags: cfg.availableTags || [],
                defaultTag: cfg.defaultTag || 'OK',
                searchQuery: '',
                statusSort: localStorage.getItem('dashboard_status_sort') || 'default',
                sortOption: localStorage.getItem('dashboard_sort_option') || 'ca_number_asc',
                sortCol: 'ca_number',
                sortAsc: true,
                avgAdjustmentPercent: parseInt(localStorage.getItem('dashboard_avg_adjustment') || '0'),
                avgTuningSteps: (function() {
                    try {
                        return JSON.parse(localStorage.getItem('dashboard_avg_tuning_steps') || '[]');
                    } catch(e) { return []; }
                })(),
                showTuningModal: false,
                tuningBaseUnits: 50,
                customTuningStep: 10,
                showMeterHistoryModal: false,
                activeHistoryCa: '',
                activeHistoryConsumerName: '',
                meterHistoryLoading: false,
                meterHistoryData: null,

                // Consumer Mobile Management
                copiedMobileId: null,
                copiedMobileTimeout: null,
                showMobileModal: false,
                editingMobileBill: null,
                mobileInput: '',
                savingMobile: false,
                mobileModalError: null,
                showBulkMobileModal: false,
                bulkMobileText: '',
                submittingBulkMobile: false,
                bulkMobileResult: null,
                bulkMobileError: null,

                // FieldDesk Bridge (Module 11)
                showFieldDeskModal: false,
                fieldDeskBill: null,
                fieldDeskAction: null,
                fieldDeskLoading: false,
                fieldDeskGpsLoading: false,
                quickDeskForm: {
                    category_id: '1',
                    target_date: new Date().toISOString().split('T')[0],
                    target_amount: '',
                    priority: 'normal',
                    private_note: '',
                    mobile: '',
                    latitude: '',
                    longitude: '',
                    location_accuracy: null,
                    save_to_consumer: true
                },

                // Dynamic Counts & Stats
                counts: Object.assign({
                    all: 0,
                    pending: 0,
                    submitted: 0,
                    critical: 0,
                    doubt: 0,
                    missing_pdf: 0,
                    filtered_units: 0,
                    filtered_amount: 0,
                    total_consumers: 0,
                    basis_ok: 0,
                    basis_lk: 0,
                    basis_md: 0,
                    basis_pl: 0,
                    basis_rn: 0
                }, cfg.counts || {}),

                init() {
                    // Check URL params first to override storage if specified
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('mru_id')) {
                        this.filterMru = urlParams.get('mru_id');
                        localStorage.setItem('dashboard_mru', this.filterMru);
                    }

                    // Populate available periods for currently selected MRU
                    this.updateAvailablePeriods(false);

                    if (urlParams.has('month') && urlParams.has('year')) {
                        this.selectedMonth = parseInt(urlParams.get('month'));
                        this.selectedYear = parseInt(urlParams.get('year'));
                        this.selectedPeriodKey = `${this.selectedMonth}_${this.selectedYear}`;
                        localStorage.setItem('dashboard_period', this.selectedPeriodKey);
                    }

                    this.cycleMonth = this.selectedMonth || new Date().getMonth() + 1;
                    this.cycleYear = this.selectedYear || new Date().getFullYear();

                    this.parseSortOption();
                    this.initNetworkListeners();

                    // 🛡️ Data Loss Prevention: BeforeUnload Tab Close / Refresh Shield
                    window.addEventListener('beforeunload', (e) => {
                        const hasInFlight = this.inFlightControllers && Object.keys(this.inFlightControllers).length > 0;
                        const hasPendingOffline = this.offlineQueue && this.offlineQueue.length > 0;
                        if (hasInFlight || hasPendingOffline) {
                            e.preventDefault();
                            e.returnValue = 'You have unsaved or pending changes syncing to the server. Are you sure you want to leave?';
                            return e.returnValue;
                        }
                    });

                    this.fetchData(1);
                },

                // --- LIFETIME OFFLINE & RECONNECTION SYNC ENGINE ---
                getCsrfToken() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (window.dashboardConfig?.csrfToken || '');
                },

                updatePendingCaSet() {
                    const map = {};
                    if (Array.isArray(this.offlineQueue)) {
                        this.offlineQueue.forEach(item => {
                            if (item.ca_number) map[String(item.ca_number)] = true;
                        });
                    }
                    this.pendingCaSet = map;
                },

                isCaPendingSync(ca) {
                    return Boolean(this.pendingCaSet && this.pendingCaSet[String(ca)]);
                },

                enqueueOfflineAction(type, payload) {
                    const ca = payload.ca_number || payload.ca || (payload.id ? String(payload.id) : null);
                    if (!Array.isArray(this.offlineQueue)) this.offlineQueue = [];
                    const existingIdx = this.offlineQueue.findIndex(q => q.type === type && (q.ca_number === ca || (payload.id && q.id === payload.id)));
                    const item = {
                        type: type,
                        payload: payload,
                        ca_number: ca,
                        id: payload.id || null,
                        queued_at: new Date().toISOString()
                    };
                    if (existingIdx !== -1) {
                        this.offlineQueue[existingIdx] = item;
                    } else {
                        this.offlineQueue.push(item);
                    }
                    try {
                        localStorage.setItem('nbpdcl_offline_queue_v1', JSON.stringify(this.offlineQueue));
                    } catch (e) {
                        console.error('Failed to save offline queue to localStorage', e);
                    }
                    this.updatePendingCaSet();
                },

                async syncOfflineQueueAndRefresh() {
                    if (this.isSyncing) return;
                    this.isSyncing = true;

                    // 1. Drain offline queue if any
                    if (this.offlineQueue && this.offlineQueue.length > 0) {
                        const queueToProcess = [...this.offlineQueue];
                        let successCount = 0;
                        for (const item of queueToProcess) {
                            try {
                                let url = '';
                                let body = {};
                                if (item.type === 'working_reading') {
                                    url = '/bills/update-working-reading';
                                    body = { id: item.payload.id, working_reading: item.payload.working_reading, force: !!item.payload.force };
                                } else if (item.type === 'status') {
                                    url = '/bills/status';
                                    body = { ca_number: item.payload.ca_number, billing_month: item.payload.billing_month, billing_year: item.payload.billing_year, status: item.payload.status };
                                } else if (item.type === 'remark') {
                                    url = '/bills/remark';
                                    body = { ca_number: item.payload.ca_number, billing_month: item.payload.billing_month, billing_year: item.payload.billing_year, remark: item.payload.remark };
                                } else if (item.type === 'tag') {
                                    url = '/bills/tag';
                                    body = { id: item.payload.id, ca_number: item.payload.ca_number, billing_month: item.payload.billing_month, billing_year: item.payload.billing_year, tag: item.payload.tag };
                                }

                                if (url) {
                                    const res = await fetch(url, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': this.getCsrfToken(),
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify(body)
                                    });
                                    if (res.ok) {
                                        successCount++;
                                        this.offlineQueue = this.offlineQueue.filter(q => !(q.type === item.type && (q.ca_number === item.ca_number || (item.id && q.id === item.id))));
                                        localStorage.setItem('nbpdcl_offline_queue_v1', JSON.stringify(this.offlineQueue));
                                    } else {
                                        const errJson = await res.json().catch(() => ({}));
                                        if (res.status === 422 || res.status === 400) {
                                            this.offlineQueue = this.offlineQueue.filter(q => !(q.type === item.type && (q.ca_number === item.ca_number || (item.id && q.id === item.id))));
                                            localStorage.setItem('nbpdcl_offline_queue_v1', JSON.stringify(this.offlineQueue));
                                            if (item.ca_number && !this.syncErrors.some(e => e.ca_number === item.ca_number && e.field === item.type)) {
                                                this.syncErrors.push({ ca_number: item.ca_number, field: item.type, msg: errJson.message || 'Offline sync rejected' });
                                            }
                                        }
                                    }
                                }
                            } catch (e) {
                                console.warn('Sync item deferred:', item, e);
                                break;
                            }
                        }
                        this.updatePendingCaSet();
                        if (successCount > 0) {
                            this.showToastNotification('✅', `Synchronized ${successCount} offline update(s) with server.`);
                        }
                    }

                    // 2. Fetch authoritative server data (silent — no preloader, preserve card position)
                    await this.fetchData(this.pagination.current_page || 1, false, true);
                    this.lastSyncedAt = new Date();
                    this.isSyncing = false;
                },

                async checkServerConnection(isManual = false) {
                    this.isCheckingConnection = true;
                    try {
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 4000);
                        const res = await fetch('/dashboard/ping', {
                            method: 'GET',
                            headers: { 'Accept': 'application/json' },
                            signal: controller.signal
                        });
                        clearTimeout(timeoutId);

                        if (res.ok) {
                            const json = await res.json().catch(() => ({}));
                            if (json.csrf_token) {
                                const metaCsrf = document.querySelector('meta[name="csrf-token"]');
                                if (metaCsrf) metaCsrf.setAttribute('content', json.csrf_token);
                            }

                            const wasDisconnected = (!this.isOnline || !this.isServerReachable);
                            this.isOnline = true;
                            this.isServerReachable = true;

                            if (isManual) {
                                // User clicked manual sync — full refresh with toast
                                await this.syncOfflineQueueAndRefresh();
                                this.showToastNotification('🟢', 'Connected! Data refreshed from server.');
                            } else if (wasDisconnected) {
                                // Just reconnected after being offline — sync silently
                                await this.syncOfflineQueueAndRefresh();
                            } else if (this.offlineQueue && this.offlineQueue.length > 0) {
                                // Online but have pending queue items — drain queue only, NO full data refresh
                                await this.syncOfflineQueueAndRefresh();
                            }
                            // Otherwise: routine heartbeat while online with no queue — do nothing (just CSRF refresh above)
                        } else {
                            this.isServerReachable = false;
                            if (isManual) {
                                this.showToastNotification('📡', 'Server responded with error. Working offline safely.');
                            }
                        }
                    } catch (err) {
                        this.isServerReachable = false;
                        if (isManual) {
                            this.showToastNotification('📡', 'Server unreachable. Working offline safely.');
                        }
                    } finally {
                        this.isCheckingConnection = false;
                    }
                },

                forceSync() {
                    this.checkServerConnection(true);
                },

                initNetworkListeners() {
                    this.updatePendingCaSet();
                    this._lastConnectionCheckAt = Date.now();

                    // Helper: is user actively typing/interacting with form controls?
                    const isUserBusy = () => {
                        const el = document.activeElement;
                        if (!el) return false;
                        const tag = el.tagName.toLowerCase();
                        return (tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable);
                    };

                    // 1. Browser online/offline events
                    window.addEventListener('online', () => {
                        this.isOnline = true;
                        this.checkServerConnection(false);
                        this._lastConnectionCheckAt = Date.now();
                    });

                    window.addEventListener('offline', () => {
                        this.isOnline = false;
                        this.isServerReachable = false;
                        this.showToastNotification('📡', 'Connection lost. Working offline safely — changes saved locally.');
                    });

                    // 2. Tab focus / screen wakeup — only ping if away for 30+ seconds AND user is not mid-input
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            const elapsed = Date.now() - (this._lastConnectionCheckAt || 0);
                            if (elapsed > 30000 && !isUserBusy()) {
                                this.checkServerConnection(false);
                                this._lastConnectionCheckAt = Date.now();
                            }
                        }
                    });

                    // 3. Offline queue retry — every 15s (was 5s) only when disconnected or queue has items
                    setInterval(() => {
                        if ((!this.isOnline || !this.isServerReachable || (this.offlineQueue && this.offlineQueue.length > 0)) && !isUserBusy()) {
                            this.checkServerConnection(false);
                            this._lastConnectionCheckAt = Date.now();
                        }
                    }, 15000);

                    // 4. General heartbeat — every 5 min (was 60s) just to keep CSRF fresh, NOT to reload data
                    setInterval(() => {
                        if (this.isOnline && this.isServerReachable && !this.isSyncing && !isUserBusy()) {
                            this.checkServerConnection(false);
                            this._lastConnectionCheckAt = Date.now();
                        }
                    }, 300000);
                },

                setViewMode(mode) {
                    const prevMode = this.viewMode;
                    this.viewMode = mode;
                    localStorage.setItem('dashboard_view_mode', mode);

                    // When switching to Card View, ensure all available cycle records are loaded for continuous sliding
                    if (mode === 'card' && (prevMode !== 'card' || (this.pagination.total > this.items.length))) {
                        this.fetchData(1);
                    } else if (mode === 'table' && prevMode === 'card') {
                        this.fetchData(1);
                    }
                },

                updateAvailablePeriods(triggerFetch = true) {
                    const availableMruKeys = Object.keys(this.mruPeriodsMap);
                    if (availableMruKeys.length > 0 && !availableMruKeys.includes(String(this.filterMru))) {
                        this.filterMru = String(cfg.selectedMruId || availableMruKeys[0]);
                        localStorage.setItem('dashboard_mru', this.filterMru);
                    }

                    const mruKey = String(this.filterMru || '');
                    this.availablePeriods = this.mruPeriodsMap[mruKey] || [];

                    const hasCurrent = this.availablePeriods.some(p => p.key === this.selectedPeriodKey);
                    if (!hasCurrent) {
                        if (this.availablePeriods.length > 0) {
                            const first = this.availablePeriods[0];
                            this.selectedPeriodKey = first.key;
                            this.selectedMonth = first.month;
                            this.selectedYear = first.year;
                        } else {
                            this.selectedPeriodKey = String(cfg.selectedMonth || '') + '_' + String(cfg.selectedYear || '');
                            this.selectedMonth = cfg.selectedMonth;
                            this.selectedYear = cfg.selectedYear;
                        }
                        localStorage.setItem('dashboard_period', this.selectedPeriodKey);
                    } else if (this.selectedPeriodKey) {
                        const parts = this.selectedPeriodKey.split('_');
                        this.selectedMonth = parseInt(parts[0]);
                        this.selectedYear = parseInt(parts[1]);
                    }

                    this.cycleMonth = this.selectedMonth || new Date().getMonth() + 1;
                    this.cycleYear = this.selectedYear || new Date().getFullYear();

                    if (triggerFetch) {
                        this.fetchData(1);
                    }
                },

                onMruChange() {
                    localStorage.setItem('dashboard_mru', this.filterMru);
                    this.updateAvailablePeriods(true);
                },

                onPeriodChange() {
                    if (!this.selectedPeriodKey) return;
                    const parts = this.selectedPeriodKey.split('_');
                    this.selectedMonth = parseInt(parts[0]);
                    this.selectedYear = parseInt(parts[1]);
                    this.cycleMonth = this.selectedMonth;
                    this.cycleYear = this.selectedYear;
                    localStorage.setItem('dashboard_period', this.selectedPeriodKey);
                    this.fetchData(1);
                },

                onStatusSortChange() {
                    localStorage.setItem('dashboard_status_sort', this.statusSort);
                    this.currentCardIndex = 0;
                    this.fetchData(1);
                },

                onSortOptionChange() {
                    localStorage.setItem('dashboard_sort_option', this.sortOption);
                    this.parseSortOption();
                    this.currentCardIndex = 0;
                    this.fetchData(1);
                },

                toggleSort(col) {
                    if (this.sortCol === col) {
                        this.sortAsc = !this.sortAsc;
                    } else {
                        this.sortCol = col;
                        this.sortAsc = true;
                    }
                    this.sortOption = `${this.sortCol}_${this.sortAsc ? 'asc' : 'desc'}`;
                    localStorage.setItem('dashboard_sort_option', this.sortOption);
                    this.currentCardIndex = 0;
                    this.fetchData(1);
                },

                setBasisFilter(b) {
                    this.basisFilter = b;
                    localStorage.setItem('dashboard_basis_filter', b);
                    this.currentCardIndex = 0;
                    this.fetchData(1);
                },

                onBasisFilterChange() {
                    localStorage.setItem('dashboard_basis_filter', this.basisFilter);
                    this.currentCardIndex = 0;
                    this.fetchData(1);
                },

                parseSortOption() {
                    const parts = this.sortOption.split('_');
                    const dir = parts.pop();
                    this.sortCol = parts.join('_');
                    this.sortAsc = (dir === 'asc');
                },

                fetchData(page = 1, append = false, silent = false) {
                    // Track the active card's CA so we can restore position after silent refresh
                    const _silentActiveCa = silent && this.items.length > 0
                        ? (this.items[this.currentCardIndex] || {}).ca_number
                        : null;

                    if (page === 1 && !append && !silent) {
                        this.currentCardIndex = 0;
                    }

                    if (append) {
                        this.loadingMoreCards = true;
                    } else if (!silent) {
                        this.loading = true;
                    }

                    const url = new URL('/dashboard/data', window.location.origin);
                    url.searchParams.append('page', page);
                    url.searchParams.append('month', this.selectedMonth);
                    url.searchParams.append('year', this.selectedYear);
                    if (this.filterMru) url.searchParams.append('mru_id', this.filterMru);
                    if (this.filterStatus && this.filterStatus !== 'all') url.searchParams.append('filter', this.filterStatus);
                    if (this.basisFilter && this.basisFilter !== 'all') url.searchParams.append('basis_filter', this.basisFilter);
                    if (this.tagFilter && this.tagFilter !== 'all') url.searchParams.append('tag_filter', this.tagFilter);
                    if (this.searchQuery) url.searchParams.append('search', this.searchQuery);
                    url.searchParams.append('status_sort', this.statusSort);
                    url.searchParams.append('sort_col', this.sortCol);
                    url.searchParams.append('sort_asc', this.sortAsc ? 'true' : 'false');
                    if (this.avgAdjustmentPercent) url.searchParams.append('adjustment_percent', this.avgAdjustmentPercent);
                    if (this.avgTuningSteps && this.avgTuningSteps.length > 0) {
                        url.searchParams.append('tuning_steps', JSON.stringify(this.avgTuningSteps));
                    }

                    // Standard enterprise pagination: 50 records per page for both table and card views
                    if (!append) {
                        url.searchParams.append('per_page', '50');
                    }

                    if (!append && this._fetchDataController) {
                        try { this._fetchDataController.abort(); } catch (e) {}
                    }
                    if (!append) {
                        this._fetchDataController = new AbortController();
                    }
                    const fetchSignal = (!append && this._fetchDataController) ? this._fetchDataController.signal : null;

                    return fetch(url, fetchSignal ? { signal: fetchSignal } : undefined)
                        .then(res => {
                            if (!res.ok) throw new Error('Network response not ok: ' + res.status);
                            return res.json();
                        })
                        .then(json => {
                            if (json.success) {
                                this.isServerReachable = true;
                                const mappedIncoming = json.data.map(b => {
                                    b._lastSavedRemark = b.remark || '';

                                    // Overlay pending offline edits so in-flight local work is never lost on refresh
                                    if (this.offlineQueue && this.offlineQueue.length > 0) {
                                        const pendingForBill = this.offlineQueue.filter(q => 
                                            q.ca_number === b.ca_number || (b.id && q.id === b.id)
                                        );
                                        pendingForBill.forEach(q => {
                                            if (q.type === 'working_reading' && q.payload && q.payload.working_reading !== undefined) {
                                                b.working_reading = q.payload.working_reading;
                                                const prevNum = parseInt(b.db_prev_reading) || parseInt(b.previous_reading) || 0;
                                                const workNum = parseInt(b.working_reading) || 0;
                                                b.working_diff_units = Math.max(0, workNum - prevNum);
                                            } else if (q.type === 'status' && q.payload && q.payload.status) {
                                                b.review_status = q.payload.status;
                                            } else if (q.type === 'remark' && q.payload && q.payload.remark !== undefined) {
                                                b.remark = q.payload.remark;
                                                b._lastSavedRemark = q.payload.remark;
                                            } else if (q.type === 'tag' && q.payload && q.payload.tag) {
                                                b.tag = q.payload.tag;
                                                b.display_tag = this.getTagDisplayLabel(q.payload.tag);
                                                b.full_tag = this.getTagFullLabel(q.payload.tag);
                                            }
                                        });
                                    }
                                    return b;
                                });

                                if (append) {
                                    // Append incoming cards without duplicates
                                    const existingIds = new Set(this.items.map(b => b.id));
                                    const freshItems = mappedIncoming.filter(b => !existingIds.has(b.id));
                                    this.items = [...this.items, ...freshItems];
                                } else {
                                    this.items = mappedIncoming;
                                }

                                // Restore card position after silent background refresh
                                if (_silentActiveCa && this.items.length > 0) {
                                    const restoredIdx = this.items.findIndex(b => b.ca_number === _silentActiveCa);
                                    if (restoredIdx !== -1) {
                                        this.currentCardIndex = restoredIdx;
                                    }
                                    // If CA no longer in list (e.g. filter change), keep current index clamped
                                    else if (this.currentCardIndex >= this.items.length) {
                                        this.currentCardIndex = Math.max(0, this.items.length - 1);
                                    }
                                }

                                this.updatePendingCaSet();
                                this.pagination = json.pagination;
                                if (json.counts) this.counts = json.counts;
                                if (json.filtered_units !== undefined) this.counts.filtered_units = json.filtered_units;
                                if (json.filtered_amount !== undefined) this.counts.filtered_amount = json.filtered_amount;
                                if (json.color_settings) this.colorSettings = json.color_settings;
                                if (json.available_periods) {
                                    this.availablePeriods = json.available_periods;
                                    const mruKey = String(this.filterMru || '');
                                    this.mruPeriodsMap[mruKey] = json.available_periods;
                                    if (this.availablePeriods.length > 0) {
                                        const hasCurrent = this.availablePeriods.some(p => p.key === this.selectedPeriodKey);
                                        if (!hasCurrent) {
                                            this.selectedPeriodKey = this.availablePeriods[0].key;
                                            this.selectedMonth = this.availablePeriods[0].month;
                                            this.selectedYear = this.availablePeriods[0].year;
                                            this.cycleMonth = this.selectedMonth;
                                            this.cycleYear = this.selectedYear;
                                        }
                                    }
                                }
                            }
                            this.loading = false;
                            this.loadingMoreCards = false;
                        })
                        .catch(err => {
                            if (err && err.name === 'AbortError') {
                                return; // Stale in-flight request aborted cleanly
                            }
                            console.warn('fetchData error (server offline/unreachable):', err);
                            this.isServerReachable = false;
                            this.loading = false;
                            this.loadingMoreCards = false;
                        });
                },

                fetchMoreCards() {
                    if (this.loadingMoreCards || this.loading) return Promise.resolve();
                    if (!this.pagination.last_page || this.pagination.current_page >= this.pagination.last_page) return Promise.resolve();

                    const nextPage = (this.pagination.current_page || 1) + 1;
                    return this.fetchData(nextPage, true);
                },

                getVisibleCardDots() {
                    if (!this.items || this.items.length === 0) return [];
                    const total = this.items.length;
                    if (total <= 25) {
                        return this.items.map((b, idx) => ({ id: b.id, index: idx }));
                    }
                    const windowSize = 25;
                    let start = Math.max(0, this.currentCardIndex - Math.floor(windowSize / 2));
                    let end = start + windowSize;
                    if (end > total) {
                        end = total;
                        start = Math.max(0, end - windowSize);
                    }
                    return this.items.slice(start, end).map((b, sliceIdx) => ({
                        id: b.id,
                        index: start + sliceIdx
                    }));
                },

                // PDF Viewer Modal Methods
                openPdfModal(bill) {
                    this.activePdfBill = bill;
                    this.showPdfViewerModal = true;
                },

                getPdfBillIndex() {
                    if (!this.activePdfBill) return 0;
                    return this.items.findIndex(b => b.ca_number === this.activePdfBill.ca_number);
                },

                navigatePdfBill(direction) {
                    const curIdx = this.getPdfBillIndex();
                    const newIdx = curIdx + direction;
                    if (newIdx >= 0 && newIdx < this.items.length) {
                        this.activePdfBill = this.items[newIdx];
                    }
                },

                printPdfIframe() {
                    const iframe = document.getElementById('pdfViewerIframe');
                    if (iframe && iframe.contentWindow) {
                        iframe.contentWindow.print();
                    }
                },

                deleteBillPdf(bill) {
                    if (!bill || !bill.id) return;
                    if (!confirm(`Delete downloaded PDF for CA ${bill.ca_number} from storage and reset status to Pending?`)) return;
                    fetch('/bills/delete-pdf', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ id: bill.id })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.showToastNotification('🗑️', data.message);
                            bill.has_pdf = false;
                            bill.pdf_path = null;
                            bill.official_pdf_reading = null;
                            bill.pdf_sync_status = 'awaiting';
                            this.showPdfViewerModal = false;
                            this.fetchData(this.pagination.current_page || 1);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToastNotification('❌', 'Error deleting PDF.');
                    });
                },

                // Single CA Real-Time Download
                downloadSingleBill(bill, explicitCa = null) {
                    const ca = explicitCa || bill?.ca_number;
                    if (!ca) return;
                    this.syncingSingle = ca;

                    fetch('/bills/download-single', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ca_number: ca,
                            billing_month: this.selectedMonth,
                            billing_year: this.selectedYear,
                            mru_id: this.filterMru || null
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.syncingSingle = null;
                        if (data.success && data.bill) {
                            const idx = this.items.findIndex(i => i.ca_number === ca);
                            if (idx !== -1) {
                                this.items[idx].has_pdf = true;
                                this.items[idx].id = data.bill.id;
                                this.items[idx].consumer_name = data.bill.consumer_name;
                                this.items[idx].total_amount = data.bill.total_amount;
                                this.items[idx].units_consumed = data.bill.units_consumed;
                                this.items[idx].current_reading = data.bill.current_reading;
                                this.items[idx].previous_reading = data.bill.previous_reading;
                                this.items[idx].meter_no = data.bill.meter_no;
                                this.items[idx].bill_month_label = data.bill.bill_month_label;
                            }
                            if (this.activePdfBill && this.activePdfBill.ca_number === ca) {
                                this.activePdfBill = { ...this.activePdfBill, ...data.bill, has_pdf: true };
                            }
                            this.showToastNotification('✅', `Bill for CA ${ca} downloaded successfully.`);
                            this.fetchData(this.pagination.current_page || 1);
                        } else {
                            this.showToastNotification('❌', data.message || `Failed to download CA ${ca}`);
                        }
                    })
                    .catch(err => {
                        this.syncingSingle = null;
                        this.showToastNotification('❌', `Download error for CA ${ca}`);
                    });
                },

                // Incremental Sync Missing Bills
                syncMissingBills() {
                    this.syncingMissing = true;

                    fetch('/bills/sync-missing', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            billing_month: this.selectedMonth,
                            billing_year: this.selectedYear,
                            mru_id: this.filterMru || null
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.syncingMissing = false;
                        if (data.success) {
                            this.showToastNotification('⚡', data.message);
                            this.fetchData(1);
                        } else {
                            this.showToastNotification('❌', data.message || 'Sync failed.');
                        }
                    })
                    .catch(err => {
                        this.syncingMissing = false;
                        this.showToastNotification('❌', 'Error syncing missing bills.');
                    });
                },

                // Quick Pull CA Execution
                executeQuickPull() {
                    if (!this.quickPullCa) return;
                    this.quickPullLoading = true;
                    this.quickPullResult = null;

                    fetch('/bills/download-single', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ca_number: this.quickPullCa.trim(),
                            billing_month: this.selectedMonth,
                            billing_year: this.selectedYear,
                            mru_id: this.filterMru || null
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.quickPullLoading = false;
                        this.quickPullResult = data;
                        if (data.success) {
                            this.showToastNotification('✅', data.message);
                            this.fetchData(1);
                        }
                    })
                    .catch(err => {
                        this.quickPullLoading = false;
                        this.quickPullResult = { success: false, message: 'Server connection error.' };
                    });
                },

                // 🔒 Check if bill is locked (submitted and not explicitly unlocked)
                isBillLocked(bill) {
                    if (!bill) return false;
                    return (bill.review_status === 'submitted') && !bill._unlocked;
                },

                // 🔓 Explicit unlock/re-lock toggle for submitted bills
                toggleUnlockBill(bill) {
                    if (!bill) return;
                    bill._unlocked = !bill._unlocked;
                    if (bill._unlocked) {
                        this.showToastNotification('🔓', `Unlocked editing for CA ${bill.ca_number}.`);
                        this.$nextTick(() => {
                            const el = (this.viewMode === 'table')
                                ? (document.getElementById('working-reading-input-table-' + bill.id) || document.getElementById('working-reading-input-' + bill.id))
                                : (document.getElementById('working-reading-input-' + bill.id) || document.getElementById('working-reading-input-table-' + bill.id));
                            if (el) {
                                el.focus();
                                el.select();
                            }
                        });
                    } else {
                        this.showToastNotification('🔒', `Re-locked CA ${bill.ca_number}.`);
                    }
                },

                // ✍️ Save Working Reading via AJAX with Invariant Checks & Offline Resilience
                // ✍️ Save Working Reading via AJAX with Invariant Checks, AbortController, Sequencing & Auto-Revert
                saveWorkingReading(bill, forceFlag = false, source = null) {
                    if (!bill.id || bill.working_reading === undefined || bill.working_reading === null) return;
                    const prevNum = parseInt(bill.db_prev_reading) || parseInt(bill.previous_reading) || 0;
                    const workNum = parseInt(bill.working_reading) || 0;
                    const pdfNum = parseInt(bill.official_pdf_reading);

                    // 1. Take Immutable Pre-Mutation Snapshot for Guaranteed Rollback
                    const prevSavedReading = bill._savedWorkingReading !== undefined ? bill._savedWorkingReading : (bill.working_reading || '');
                    const snapshot = {
                        working_reading: prevSavedReading,
                        working_diff_units: bill.working_diff_units,
                        pdf_sync_status: bill.pdf_sync_status,
                        pdf_delta: bill.pdf_delta,
                        reading_source: bill.reading_source,
                        is_manual: bill.is_manual,
                        is_projected: bill.is_projected
                    };

                    bill.working_diff_units = Math.max(0, workNum - prevNum);

                    // Recompute live status
                    if (!isNaN(pdfNum) && pdfNum > 0) {
                        if (workNum > pdfNum) {
                            bill.pdf_sync_status = 'ahead';
                            bill.pdf_delta = workNum - pdfNum;
                        } else if (workNum === pdfNum) {
                            bill.pdf_sync_status = 'matched';
                            bill.pdf_delta = 0;
                        } else {
                            bill.pdf_sync_status = 'invalid_behind';
                            bill.pdf_delta = workNum - pdfNum;
                            this.showToastNotification('⚠️', `Warning: Working reading (${workNum}) is less than PDF reading (${pdfNum})!`);
                        }
                    }

                    const isSubmitted = (bill.review_status === 'submitted');
                    const computedForce = isSubmitted && !!bill._unlocked;
                    const finalForce = forceFlag || computedForce;
                    const saveSource = source || bill.reading_source || (bill.is_manual ? 'manual' : 'auto');

                    // 2. Abort any pending in-flight request for this specific CA (No Race Conditions)
                    const abortKey = `reading_${bill.ca_number}`;
                    this.abortInFlight(abortKey);
                    const controller = new AbortController();
                    this.inFlightControllers[abortKey] = controller;

                    // 3. Monotonic Sequence Revision Tracking (Discards Out-of-Order Stale Responses)
                    bill._readingSeq = (bill._readingSeq || 0) + 1;
                    const reqSeq = bill._readingSeq;

                    // Check offline / server unreachable state
                    if (!this.isOnline || !this.isServerReachable) {
                        this.enqueueOfflineAction('working_reading', {
                            id: bill.id,
                            ca_number: bill.ca_number,
                            working_reading: String(bill.working_reading).trim(),
                            source: saveSource,
                            force: finalForce
                        });
                        bill._savedWorkingReading = bill.working_reading;
                        bill._syncError = false;
                        this.showToastNotification('☁️', `Reading ${bill.working_reading} saved locally (Offline mode).`);
                        return;
                    }

                    fetch('/bills/update-working-reading', {
                        method: 'POST',
                        keepalive: true,
                        signal: controller.signal,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            id: bill.id,
                            working_reading: String(bill.working_reading).trim(),
                            source: saveSource,
                            force: finalForce
                        })
                    })
                    .then(r => {
                        if (r.status === 422) {
                            return r.json().then(errData => {
                                if (errData.requires_override) {
                                    this.showToastNotification('🔒', errData.message || 'Bill is submitted and locked. Unlock first to update.');
                                }
                                const err = new Error(errData.message || 'Validation error');
                                err.isValidationError = true;
                                throw err;
                            });
                        }
                        if (!r.ok) throw new Error('Network error ' + r.status);
                        return r.json();
                    })
                    .then(data => {
                        // Discard if a newer request was dispatched while this was flying
                        if (bill._readingSeq !== reqSeq) return;

                        if (data.success) {
                            bill._savedWorkingReading = bill.working_reading;
                            bill._syncError = false;
                            bill._syncErrorMsg = null;
                            this.syncErrors = this.syncErrors.filter(e => !(e.ca_number === bill.ca_number && e.field === 'Reading'));
                            if (data.reading_source) {
                                bill.reading_source = data.reading_source;
                                bill.is_manual = (data.reading_source === 'manual');
                                bill.is_projected = (data.reading_source !== 'manual');
                            }
                            this.showToastNotification('💾', 'Working reading saved: ' + bill.working_reading);
                        } else {
                            throw new Error(data.message || 'Save failed');
                        }
                    })
                    .catch(err => {
                        if (err.name === 'AbortError') {
                            // Supressed clean abort for faster typing
                            return;
                        }
                        console.warn('Working reading save failed:', err);

                        if (err.isValidationError || (err.message && (err.message.includes('locked') || err.message.includes('submitted')))) {
                            // Guaranteed Visual Revert Alert: Roll back on server rejection
                            bill.working_reading = snapshot.working_reading;
                            bill.working_diff_units = snapshot.working_diff_units;
                            bill.pdf_sync_status = snapshot.pdf_sync_status;
                            bill.pdf_delta = snapshot.pdf_delta;
                            bill._syncError = true;
                            bill._syncErrorMsg = err.message || 'Rejected by server';
                            if (!this.syncErrors.some(e => e.ca_number === bill.ca_number && e.field === 'Reading')) {
                                this.syncErrors.push({ ca_number: bill.ca_number, field: 'Reading', msg: bill._syncErrorMsg });
                            }
                            this.showToastNotification('⚠️', `Failed to update reading: ${err.message || 'Validation rejected'} (Reverted).`);
                            return;
                        }

                        // Fall back to offline queue on connection loss
                        this.isServerReachable = false;
                        this.enqueueOfflineAction('working_reading', {
                            id: bill.id,
                            ca_number: bill.ca_number,
                            working_reading: String(bill.working_reading).trim(),
                            source: saveSource,
                            force: finalForce
                        });
                        this.showToastNotification('☁️', `Working reading saved locally (Connection lost).`);
                    })
                    .finally(() => {
                        delete this.inFlightControllers[abortKey];
                    });
                },

                // ⚡ Auto-Fill Working Reading with (Previous + Average) ensuring >= PDF Reading
                autoFillWorkingReading(bill) {
                    if (this.isBillLocked(bill)) {
                        this.showToastNotification('🔒', 'Cannot auto-fill: Bill is submitted and locked. Unlock first.');
                        return;
                    }

                    const prev = parseInt(bill.db_prev_reading) || parseInt(bill.previous_reading) || 0;
                    const baseAvg = parseInt(bill.base_avg_units) || parseInt(bill.smart_avg_units) || 50;
                    const effectiveAvg = this.getCompoundedUnits(baseAvg);
                    const netPercent = this.getNetTuningPercent(baseAvg);
                    let target = parseInt(bill.projected_reading) || (prev > 0 ? (prev + effectiveAvg) : effectiveAvg);

                    const pdfNum = parseInt(bill.official_pdf_reading);
                    if (!isNaN(pdfNum) && target < pdfNum) {
                        target = pdfNum; // Guaranteed never < PDF
                    }

                    const currentVal = (bill.working_reading !== undefined && bill.working_reading !== null) ? String(bill.working_reading).trim() : '';
                    const targetStr = String(target);

                    // Manual Override Protection:
                    // ONLY prompt if the user explicitly set this bill as manual!
                    const isManualUserEntry = (bill.reading_source === 'manual' || bill.is_manual);
                    if (isManualUserEntry && currentVal !== '' && currentVal !== targetStr) {
                        const adjText = netPercent !== 0 ? ` [${netPercent > 0 ? '+' : ''}${netPercent}% tuning = ${effectiveAvg} kWh]` : '';
                        const confirmMsg = `This account currently has a manual reading of ${currentVal} kWh.\n\nAre you sure you want to replace it with auto-fill ${targetStr} kWh (Prev ${prev} + Base ${baseAvg}${adjText})?`;
                        if (!confirm(confirmMsg)) {
                            return;
                        }
                    }

                    const oldReading = currentVal;
                    const oldManual = bill.is_manual;
                    const oldSource = bill.reading_source;

                    bill.working_reading = targetStr;
                    bill.is_projected = true;
                    bill.is_manual = false;
                    bill.reading_source = 'auto';
                    this.saveWorkingReading(bill, false, 'auto');

                    if (oldReading && oldReading !== targetStr) {
                        this.showToastNotification(
                            '⚡',
                            `Auto-filled CA ${bill.ca_number} with ${targetStr}` + (isManualUserEntry ? ` (replaced ${oldReading})` : ''),
                            {
                                kind: 'working_reading',
                                bill: bill,
                                prevReading: oldReading,
                                prevManual: oldManual,
                                prevSource: oldSource
                            }
                        );
                    } else {
                        this.showToastNotification('⚡', `Auto-filled CA ${bill.ca_number} with ${targetStr}`, null);
                    }
                },

                // ⚡ Bulk Auto-Project All Unfilled Readings
                bulkAutoProjectAll() {
                    if (!confirm(`Auto-project working readings (Previous + Avg) for all active accounts in this cycle?\n\n(Submitted bills and manual custom overrides will remain protected.)`)) return;
                    fetch('/bills/bulk-project-readings', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            month: this.selectedMonth,
                            year: this.selectedYear,
                            mru_id: this.filterMru || null,
                            adjustment_percent: this.avgAdjustmentPercent || 0,
                            tuning_steps: this.avgTuningSteps || []
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.showToastNotification('⚡', data.message);
                            this.fetchData(this.pagination.current_page || 1);
                        }
                    })
                    .catch(err => console.error(err));
                },

                // --- SMART AVERAGE TUNING & SEQUENTIAL COMPOUNDING ---
                openTuningModal() {
                    this.showTuningModal = true;
                },

                addTuningStep(pct) {
                    const val = parseFloat(pct);
                    if (!isNaN(val) && val !== 0) {
                        this.avgTuningSteps.push(val);
                        localStorage.setItem('dashboard_avg_tuning_steps', JSON.stringify(this.avgTuningSteps));
                    }
                },

                removeTuningStep(idx) {
                    this.avgTuningSteps.splice(idx, 1);
                    localStorage.setItem('dashboard_avg_tuning_steps', JSON.stringify(this.avgTuningSteps));
                },

                clearTuning() {
                    this.avgTuningSteps = [];
                    this.avgAdjustmentPercent = 0;
                    localStorage.removeItem('dashboard_avg_tuning_steps');
                    localStorage.setItem('dashboard_avg_adjustment', '0');
                    fetch('/dashboard/tuning/reset', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        }
                    }).catch(e => console.error(e));
                },

                applyTuningAndFetch() {
                    this.showTuningModal = false;
                    localStorage.setItem('dashboard_avg_tuning_steps', JSON.stringify(this.avgTuningSteps));
                    localStorage.setItem('dashboard_avg_adjustment', String(this.getNetTuningPercent(this.tuningBaseUnits)));

                    // Save to server session
                    fetch('/dashboard/tuning', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            steps: this.avgTuningSteps,
                            percent: this.getNetTuningPercent(this.tuningBaseUnits),
                            base_units: this.tuningBaseUnits
                        })
                    }).catch(e => console.error(e));

                    this.fetchData(1);
                    this.showToastNotification('⚡', `Tuned average applied: ${this.getCompoundedUnits(this.tuningBaseUnits)} kWh (${(this.getNetTuningPercent(this.tuningBaseUnits) >= 0 ? '+' : '')}${this.getNetTuningPercent(this.tuningBaseUnits)}%)`);
                },

                getCompoundedStepsDetails(base = 50) {
                    let cur = base;
                    const details = [];
                    for (const step of this.avgTuningSteps) {
                        const before = cur;
                        cur = cur * (1 + (step / 100));
                        details.push({
                            percent: step,
                            before: Math.round(before * 10) / 10,
                            after: Math.round(cur * 10) / 10
                        });
                    }
                    return details;
                },

                getCompoundedUnits(base = 50) {
                    let cur = base;
                    for (const step of this.avgTuningSteps) {
                        cur = cur * (1 + (step / 100));
                    }
                    return Math.max(1, Math.round(cur));
                },

                getNetTuningPercent(base = 50) {
                    if (this.avgTuningSteps.length === 0) {
                        return this.avgAdjustmentPercent || 0;
                    }
                    const tuned = this.getCompoundedUnits(base);
                    return Math.round(((tuned - base) / base) * 100);
                },

                // --- 2D METER READING HISTORY MODAL ---
                openMeterHistoryModal(caNumber, consumerName = '') {
                    this.activeHistoryCa = caNumber;
                    this.activeHistoryConsumerName = consumerName;
                    this.showMeterHistoryModal = true;
                    this.meterHistoryLoading = true;
                    this.meterHistoryData = null;

                    fetch(`/bills/matrix/${caNumber}`)
                        .then(r => r.json())
                        .then(res => {
                            this.meterHistoryLoading = false;
                            if (res.success && res.data) {
                                this.meterHistoryData = res.data;
                            }
                        })
                        .catch(err => {
                            this.meterHistoryLoading = false;
                            console.error(err);
                        });
                },

                // --- CONSUMER MOBILE NUMBER MANAGEMENT ---
                copyMobile(bill) {
                    if (!bill || !bill.mobile) return;
                    const mob = bill.mobile;
                    const self = this;
                    const onCopied = () => {
                        self.copiedMobileId = bill.id;
                        if (self.copiedMobileTimeout) clearTimeout(self.copiedMobileTimeout);
                        self.copiedMobileTimeout = setTimeout(() => {
                            self.copiedMobileId = null;
                        }, 2000);
                        self.showToastNotification('📱', `Copied Mobile: ${mob}`, null);
                    };

                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(mob)
                            .then(onCopied)
                            .catch(err => {
                                console.warn('Clipboard API failed, using fallback:', err);
                                self.fallbackCopyText(mob, onCopied);
                            });
                    } else {
                        self.fallbackCopyText(mob, onCopied);
                    }
                },

                openMobileModal(bill) {
                    if (!bill) return;
                    this.editingMobileBill = bill;
                    this.mobileInput = bill.mobile || '';
                    this.mobileModalError = null;
                    this.showMobileModal = true;
                    this.$nextTick(() => {
                        const input = document.getElementById('consumerMobileInput');
                        if (input) input.focus();
                    });
                },

                async saveConsumerMobile(clearMobile = false) {
                    if (!this.editingMobileBill) return;
                    this.savingMobile = true;
                    this.mobileModalError = null;

                    const targetCa = this.editingMobileBill.ca_number;
                    const targetMobile = clearMobile ? '' : this.mobileInput.trim();

                    try {
                        const res = await fetch('/consumers/update-mobile', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ca_number: targetCa,
                                mobile: targetMobile
                            })
                        });

                        const data = await res.json();
                        if (res.ok && data.success) {
                            const newMobile = data.mobile || null;
                            this.items.forEach(item => {
                                if (item.ca_number === targetCa) {
                                    item.mobile = newMobile;
                                }
                            });
                            if (this.editingMobileBill) {
                                this.editingMobileBill.mobile = newMobile;
                            }
                            this.showToastNotification(newMobile ? '📱' : '🗑️', data.message || 'Mobile number updated', null);
                            this.showMobileModal = false;
                        } else {
                            this.mobileModalError = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Failed to update mobile number');
                        }
                    } catch (err) {
                        console.error('saveConsumerMobile error:', err);
                        this.mobileModalError = 'Network error while updating mobile number';
                    } finally {
                        this.savingMobile = false;
                    }
                },

                openBulkMobileModal() {
                    this.bulkMobileText = '';
                    this.bulkMobileResult = null;
                    this.bulkMobileError = null;
                    this.showBulkMobileModal = true;
                },

                async submitBulkMobile() {
                    if (!this.bulkMobileText.trim()) {
                        this.bulkMobileError = 'Please paste or enter at least one CA and mobile pair.';
                        return;
                    }

                    this.submittingBulkMobile = true;
                    this.bulkMobileError = null;
                    this.bulkMobileResult = null;

                    try {
                        const res = await fetch('/consumers/bulk-update-mobile', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                raw_data: this.bulkMobileText.trim()
                            })
                        });

                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.bulkMobileResult = data;
                            if (data.updated_map) {
                                this.items.forEach(item => {
                                    if (data.updated_map.hasOwnProperty(item.ca_number)) {
                                        item.mobile = data.updated_map[item.ca_number];
                                    }
                                });
                            }
                            this.showToastNotification('📱', data.message || `Updated ${data.updated_count} mobile numbers!`, null);
                        } else {
                            this.bulkMobileError = data.message || 'Failed to update bulk mobile numbers.';
                        }
                    } catch (err) {
                        console.error('submitBulkMobile error:', err);
                        this.bulkMobileError = 'Network error while processing bulk update.';
                    } finally {
                        this.submittingBulkMobile = false;
                    }
                },

                // FieldDesk Bridge Methods (Module 11)
                async openFieldDeskModal(bill) {
                    this.fieldDeskBill = bill;
                    this.fieldDeskAction = bill.field_desk_action || null;
                    this.quickDeskForm = {
                        category_id: String(cfg.fieldDeskDefaultCategoryId || '1'),
                        target_date: new Date().toISOString().split('T')[0],
                        target_amount: bill.total_amount ? Number(bill.total_amount).toFixed(2) : '',
                        priority: 'normal',
                        private_note: '',
                        mobile: bill.mobile || '',
                        latitude: bill.latitude || '',
                        longitude: bill.longitude || '',
                        location_accuracy: bill.location_accuracy || null,
                        save_to_consumer: true
                    };
                    this.showFieldDeskModal = true;
                    this.fieldDeskLoading = true;

                    try {
                        const res = await fetch(`/api/field-desk/consumer/${bill.ca_number}`);
                        const data = await res.json();
                        if (res.ok && data.success) {
                            if (data.action) {
                                this.fieldDeskAction = data.action;
                                bill.field_desk_action = data.action;
                            } else {
                                this.fieldDeskAction = null;
                                bill.field_desk_action = null;
                            }
                            if (data.consumer) {
                                if (data.consumer.mobile) bill.mobile = data.consumer.mobile;
                                if (data.consumer.latitude) {
                                    bill.latitude = data.consumer.latitude;
                                    bill.longitude = data.consumer.longitude;
                                    bill.location_accuracy = data.consumer.location_accuracy;
                                    bill.map_link = data.consumer.map_link;
                                }
                            }
                        }
                    } catch (err) {
                        console.error('Error fetching FieldDesk consumer action:', err);
                    } finally {
                        this.fieldDeskLoading = false;
                    }
                },

                captureFieldDeskGps(instantSave = false) {
                    if (!navigator.geolocation) {
                        this.showToastNotification('❌', 'Geolocation is not supported by your browser', null);
                        return;
                    }
                    this.fieldDeskGpsLoading = true;
                    navigator.geolocation.getCurrentPosition(
                        async (position) => {
                            const lat = Number(position.coords.latitude.toFixed(8));
                            const lng = Number(position.coords.longitude.toFixed(8));
                            const acc = position.coords.accuracy ? Number(position.coords.accuracy.toFixed(1)) : null;

                            this.quickDeskForm.latitude = lat;
                            this.quickDeskForm.longitude = lng;
                            this.quickDeskForm.location_accuracy = acc;
                            this.fieldDeskGpsLoading = false;

                            if (this.fieldDeskBill) {
                                this.fieldDeskBill.latitude = lat;
                                this.fieldDeskBill.longitude = lng;
                                this.fieldDeskBill.location_accuracy = acc;
                                this.fieldDeskBill.map_link = `https://www.google.com/maps?q=${lat},${lng}`;
                            }

                            const precisionMsg = acc && acc <= 10 ? `🎯 Locked! Sub-10m precision (±${Math.round(acc)}m)` : `📍 Position captured (±${Math.round(acc || 0)}m)`;
                            this.showToastNotification('🟢', precisionMsg, null);

                            // Instant save to consumer account if requested
                            if (instantSave && this.fieldDeskBill) {
                                try {
                                    const res = await fetch(`/api/field-desk/consumer/${this.fieldDeskBill.ca_number}/contact`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': this.getCsrfToken(),
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            latitude: lat,
                                            longitude: lng,
                                            location_accuracy: acc
                                        })
                                    });
                                    const json = await res.json();
                                    if (json.success) {
                                        this.showToastNotification('✅', 'GPS coordinates saved to Consumer Account!', null);
                                    }
                                } catch (e) {
                                    console.error('Error saving GPS:', e);
                                }
                            }
                        },
                        (error) => {
                            this.fieldDeskGpsLoading = false;
                            let msg = 'Failed to get location';
                            if (error.code === 1) msg = 'Location permission denied';
                            else if (error.code === 2) msg = 'Position unavailable. Check GPS';
                            else if (error.code === 3) msg = 'GPS timeout. Stand under open sky';
                            this.showToastNotification('⚠️', msg, null);
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0
                        }
                    );
                },

                setQuickDeskPreset(days) {
                    const d = new Date();
                    d.setDate(d.getDate() + days);
                    this.quickDeskForm.target_date = d.toISOString().split('T')[0];
                },

                async saveQuickDeskAction() {
                    if (!this.fieldDeskBill) return;
                    try {
                        const payload = {
                            ca_number: this.fieldDeskBill.ca_number,
                            category_id: this.quickDeskForm.category_id,
                            target_date: this.quickDeskForm.target_date,
                            priority: this.quickDeskForm.priority,
                            target_amount: this.quickDeskForm.target_amount,
                            private_note: this.quickDeskForm.private_note,
                            mru_id: this.fieldDeskBill.mru_id,
                            mobile: this.quickDeskForm.mobile,
                            latitude: this.quickDeskForm.latitude,
                            longitude: this.quickDeskForm.longitude,
                            location_accuracy: this.quickDeskForm.location_accuracy,
                            save_to_consumer: this.quickDeskForm.save_to_consumer
                        };
                        const res = await fetch('/api/field-desk/actions', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.showToastNotification('📋', 'FieldDesk action registered', null);
                            if (this.quickDeskForm.mobile) this.fieldDeskBill.mobile = this.quickDeskForm.mobile;
                            if (this.quickDeskForm.latitude && this.quickDeskForm.longitude) {
                                this.fieldDeskBill.latitude = this.quickDeskForm.latitude;
                                this.fieldDeskBill.longitude = this.quickDeskForm.longitude;
                                this.fieldDeskBill.location_accuracy = this.quickDeskForm.location_accuracy;
                                this.fieldDeskBill.map_link = `https://www.google.com/maps?q=${this.quickDeskForm.latitude},${this.quickDeskForm.longitude}`;
                            }
                            await this.openFieldDeskModal(this.fieldDeskBill);
                        } else {
                            this.showToastNotification('⚠️', data.message || 'Error saving action', null);
                        }
                    } catch (err) {
                        this.showToastNotification('❌', 'Network error', null);
                    }
                },

                async quickSnoozeFromModal(id, days) {
                    try {
                        const res = await fetch(`/api/field-desk/actions/${id}/reschedule`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ days: days })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.showToastNotification('🔄', `Snoozed +${days} days!`, null);
                            await this.openFieldDeskModal(this.fieldDeskBill);
                        }
                    } catch (err) {
                        this.showToastNotification('❌', 'Error snoozing action', null);
                    }
                },

                async completeActionFromModal(id) {
                    try {
                        const collected = (this.fieldDeskAction && this.fieldDeskAction.target_amount > 0)
                            ? (this.fieldDeskAction.remaining_amount !== undefined && this.fieldDeskAction.remaining_amount !== null ? this.fieldDeskAction.remaining_amount : this.fieldDeskAction.target_amount)
                            : null;
                        const res = await fetch(`/api/field-desk/actions/${id}/complete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                collected_amount: collected,
                                note: 'Resolved from Billing Dashboard'
                            })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.showToastNotification('✅', 'Action marked as resolved! 🎉', null);
                            if (this.fieldDeskBill) {
                                this.fieldDeskBill.field_desk_action = null;
                            }
                            this.showFieldDeskModal = false;
                        }
                    } catch (err) {
                        this.showToastNotification('❌', 'Error resolving action', null);
                    }
                },

                async logFieldDeskActivity(id, type, note) {
                    try {
                        await fetch(`/api/field-desk/actions/${id}/activity`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ action_type: type, note: note })
                        });
                    } catch (err) {}
                },

                launchBillingCycle(actionType = 'download_all') {
                    if (!this.filterMru) return;

                    this.executingAction = actionType;
                    this.cycleInProgress = true;
                    this.cycleResult = null;

                    fetch('/mrus/billing-cycle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken()
                        },
                        body: JSON.stringify({
                            mru_id: this.filterMru,
                            billing_month: this.cycleMonth,
                            billing_year: this.cycleYear,
                            action_type: actionType
                        })
                    })
                    .then(async res => {
                        const json = await res.json().catch(() => ({}));
                        if (!res.ok && !json.message) {
                            throw new Error('Server returned an error.');
                        }
                        return json;
                    })
                    .then(json => {
                        this.cycleResult = json;
                        if (json.success) {
                            const newKey = `${this.cycleMonth}_${this.cycleYear}`;
                            const mruKey = String(this.filterMru || '');
                            if (!this.mruPeriodsMap[mruKey]) this.mruPeriodsMap[mruKey] = [];
                            const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
                            const newLabel = `${monthNames[this.cycleMonth - 1]}, ${this.cycleYear}`;
                            if (!this.mruPeriodsMap[mruKey].some(p => p.key === newKey)) {
                                this.mruPeriodsMap[mruKey].unshift({
                                    key: newKey,
                                    month: this.cycleMonth,
                                    year: this.cycleYear,
                                    label: newLabel
                                });
                            }
                            this.availablePeriods = this.mruPeriodsMap[mruKey];
                            this.selectedMonth = this.cycleMonth;
                            this.selectedYear = this.cycleYear;
                            this.selectedPeriodKey = newKey;
                            localStorage.setItem('dashboard_period', this.selectedPeriodKey);
                            setTimeout(() => {
                                this.showNewCycleModal = false;
                                this.cycleInProgress = false;
                                this.fetchData(1);
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

                showToastNotification(icon, message, undoData = null) {
                    if (this.toast.timer) clearTimeout(this.toast.timer);
                    this.toast.icon = icon;
                    this.toast.message = message;
                    this.toast.undoData = undoData;
                    this.toast.show = true;

                    this.toast.timer = setTimeout(() => {
                        this.toast.show = false;
                    }, 5000);
                },

                updateBillStatus(bill, status) {
                    const prevStatus = bill.review_status || 'pending';
                    const newStatus = (prevStatus === status) ? 'pending' : status;

                    const statusLabels = {
                        submitted: 'Submitted',
                        critical: 'Critical',
                        doubt: 'Doubt',
                        pending: 'Pending'
                    };
                    const statusIcons = {
                        submitted: '✅',
                        critical: '❌',
                        doubt: '⚠️',
                        pending: '⏳'
                    };

                    // 1. Immutable Pre-Mutation Snapshot for Absolute Rollback
                    const snapshot = {
                        review_status: prevStatus,
                        _unlocked: bill._unlocked
                    };

                    // 2. Optimistically update status on UI instantly
                    bill.review_status = newStatus;
                    if (newStatus !== 'submitted') {
                        bill._unlocked = false;
                    }

                    // 3. Update live global counts immediately
                    if (prevStatus !== newStatus) {
                        if (this.counts[prevStatus] !== undefined) {
                            this.counts[prevStatus] = Math.max(0, this.counts[prevStatus] - 1);
                        }
                        if (this.counts[newStatus] !== undefined) {
                            this.counts[newStatus] = (this.counts[newStatus] || 0) + 1;
                        }
                    }

                    // 4. Handle active filter view removal with Exact Index tracking for safe re-insertion
                    let removedIndex = -1;
                    const wasFilteredOut = (this.filterStatus !== 'all' && this.filterStatus !== newStatus);
                    if (wasFilteredOut) {
                        removedIndex = this.items.findIndex(x => x.id === bill.id);
                        if (removedIndex !== -1) {
                            this.items.splice(removedIndex, 1);
                            this.pagination.total = Math.max(0, (this.pagination.total || 0) - 1);
                            if (this.currentCardIndex >= this.items.length) {
                                this.currentCardIndex = Math.max(0, this.items.length - 1);
                            }
                        }
                    }

                    // 5. Abort in-flight status request for this CA (Prevents Race Conditions)
                    const abortKey = `status_${bill.ca_number}`;
                    this.abortInFlight(abortKey);
                    const controller = new AbortController();
                    this.inFlightControllers[abortKey] = controller;

                    // 6. Monotonic Sequence Counter
                    bill._statusSeq = (bill._statusSeq || 0) + 1;
                    const reqSeq = bill._statusSeq;

                    // Check offline / server unreachable state
                    if (!this.isOnline || !this.isServerReachable) {
                        this.enqueueOfflineAction('status', {
                            id: bill.id,
                            ca_number: bill.ca_number,
                            billing_month: bill.billing_month || this.selectedMonth,
                            billing_year: bill.billing_year || this.selectedYear,
                            status: newStatus
                        });
                        bill._syncError = false;
                        this.showToastNotification(
                            '☁️',
                            `Marked CA ${bill.ca_number} as ${statusLabels[newStatus]} (Saved Offline)`,
                            {
                                kind: 'status',
                                bill: bill,
                                prevStatus: prevStatus,
                                newStatus: newStatus,
                                wasFilteredOut: wasFilteredOut,
                                removedIndex: removedIndex
                            }
                        );
                        return wasFilteredOut;
                    }

                    // Show toast with Undo option
                    this.showToastNotification(
                        statusIcons[newStatus],
                        `Marked CA ${bill.ca_number} as ${statusLabels[newStatus]}`,
                        {
                            kind: 'status',
                            bill: bill,
                            prevStatus: prevStatus,
                            newStatus: newStatus,
                            wasFilteredOut: wasFilteredOut,
                            removedIndex: removedIndex
                        }
                    );

                    // Send API request
                    fetch('/bills/status', {
                        method: 'POST',
                        keepalive: true,
                        signal: controller.signal,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ca_number: bill.ca_number,
                            billing_month: bill.billing_month || this.selectedMonth,
                            billing_year: bill.billing_year || this.selectedYear,
                            status: newStatus
                        })
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Status update failed (HTTP ' + res.status + ')');
                        return res.json();
                    })
                    .then(json => {
                        if (bill._statusSeq !== reqSeq) return;

                        if (json.success) {
                            bill._syncError = false;
                            bill._syncErrorMsg = null;
                            this.syncErrors = this.syncErrors.filter(e => !(e.ca_number === bill.ca_number && e.field === 'Status'));
                        } else {
                            throw new Error(json.message || 'Server rejected status update');
                        }
                    })
                    .catch(err => {
                        if (err.name === 'AbortError') return;

                        console.warn('Status update failed, handling rollback or offline:', err);

                        // If actual network offline failure
                        if (err.message && (err.message.includes('Failed to fetch') || err.message.includes('NetworkError'))) {
                            this.isServerReachable = false;
                            this.enqueueOfflineAction('status', {
                                id: bill.id,
                                ca_number: bill.ca_number,
                                billing_month: bill.billing_month || this.selectedMonth,
                                billing_year: bill.billing_year || this.selectedYear,
                                status: newStatus
                            });
                            this.showToastNotification('☁️', `Status saved locally for CA ${bill.ca_number} (Connection lost).`);
                            return;
                        }

                        // Guaranteed Rollback on Server Rejection
                        bill.review_status = snapshot.review_status;
                        bill._unlocked = snapshot._unlocked;
                        bill._syncError = true;
                        bill._syncErrorMsg = err.message || 'Server rejected status update';
                        if (!this.syncErrors.some(e => e.ca_number === bill.ca_number && e.field === 'Status')) {
                            this.syncErrors.push({ ca_number: bill.ca_number, field: 'Status', msg: bill._syncErrorMsg });
                        }

                        // Restore counts
                        if (this.counts[newStatus] !== undefined) {
                            this.counts[newStatus] = Math.max(0, this.counts[newStatus] - 1);
                        }
                        if (this.counts[prevStatus] !== undefined) {
                            this.counts[prevStatus] = (this.counts[prevStatus] || 0) + 1;
                        }

                        // Re-insert card at exact original index if it was removed
                        if (wasFilteredOut && removedIndex !== -1) {
                            this.items.splice(removedIndex, 0, bill);
                            this.pagination.total = (this.pagination.total || 0) + 1;
                        }

                        this.showToastNotification('⚠️', `Failed to mark CA ${bill.ca_number}: ${bill._syncErrorMsg} (Reverted)`);
                    })
                    .finally(() => {
                        delete this.inFlightControllers[abortKey];
                    });

                    return wasFilteredOut;
                },

                onRemarkFocus(bill) {
                    if (bill._lastSavedRemark === undefined) {
                        bill._lastSavedRemark = bill.remark || '';
                    }
                },

                onRemarkBlur(bill) {
                    const current = (bill.remark || '').trim();
                    const previous = (bill._lastSavedRemark !== undefined ? bill._lastSavedRemark : '').trim();

                    // Auto-save ONLY if the text actually changed upon clicking outside (blur)
                    if (current !== previous) {
                        this.saveBillRemark(bill, false, previous);
                    }
                },

                saveBillRemark(bill, isManual = false, previousRemark = null) {
                    const prev = previousRemark !== null ? previousRemark : (bill._lastSavedRemark || '');
                    const current = bill.remark || '';
                    bill._lastSavedRemark = current;

                    const abortKey = `remark_${bill.ca_number}`;
                    this.abortInFlight(abortKey);
                    const controller = new AbortController();
                    this.inFlightControllers[abortKey] = controller;

                    if (!this.isOnline || !this.isServerReachable) {
                        this.enqueueOfflineAction('remark', {
                            id: bill.id,
                            ca_number: bill.ca_number,
                            billing_month: bill.billing_month || this.selectedMonth,
                            billing_year: bill.billing_year || this.selectedYear,
                            remark: current
                        });
                        this.showToastNotification(
                            '☁️',
                            current.trim() ? `Saved note for CA ${bill.ca_number} (Offline)` : `Cleared note for CA ${bill.ca_number} (Offline)`,
                            {
                                kind: 'remark',
                                bill: bill,
                                prevRemark: prev,
                                newRemark: current
                            }
                        );
                        return;
                    }

                    fetch('/bills/remark', {
                        method: 'POST',
                        keepalive: true,
                        signal: controller.signal,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ca_number: bill.ca_number,
                            billing_month: bill.billing_month || this.selectedMonth,
                            billing_year: bill.billing_year || this.selectedYear,
                            remark: current
                        })
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Remark save error: (HTTP ' + res.status + ')');
                        return res.json();
                    })
                    .then(json => {
                        if (json.success) {
                            bill._syncError = false;
                            this.syncErrors = this.syncErrors.filter(e => !(e.ca_number === bill.ca_number && e.field === 'Remark'));
                            this.showToastNotification(
                                '💬',
                                current.trim() ? `Saved note for CA ${bill.ca_number}` : `Cleared note for CA ${bill.ca_number}`,
                                {
                                    kind: 'remark',
                                    bill: bill,
                                    prevRemark: prev,
                                    newRemark: current
                                }
                            );
                        } else {
                            throw new Error(json.message || 'Server rejected note');
                        }
                    })
                    .catch(err => {
                        if (err.name === 'AbortError') return;

                        console.warn('Remark save failed online, handling rollback or offline:', err);

                        if (err.message && (err.message.includes('Failed to fetch') || err.message.includes('NetworkError'))) {
                            this.isServerReachable = false;
                            this.enqueueOfflineAction('remark', {
                                id: bill.id,
                                ca_number: bill.ca_number,
                                billing_month: bill.billing_month || this.selectedMonth,
                                billing_year: bill.billing_year || this.selectedYear,
                                remark: current
                            });
                            this.showToastNotification('☁️', `Note saved locally for CA ${bill.ca_number} (Connection lost).`);
                            return;
                        }

                        // Roll back to previous remark on server error
                        bill.remark = prev;
                        bill._lastSavedRemark = prev;
                        bill._syncError = true;
                        bill._syncErrorMsg = err.message || 'Failed to save note';
                        if (!this.syncErrors.some(e => e.ca_number === bill.ca_number && e.field === 'Remark')) {
                            this.syncErrors.push({ ca_number: bill.ca_number, field: 'Remark', msg: bill._syncErrorMsg });
                        }
                        this.showToastNotification('⚠️', `Failed to save note for CA ${bill.ca_number} (Reverted).`);
                    })
                    .finally(() => {
                        delete this.inFlightControllers[abortKey];
                    });
                },

                clearBillRemark(bill) {
                    const prev = bill.remark || '';
                    if (!prev) return;
                    bill.remark = '';
                    this.saveBillRemark(bill, true, prev);
                },

                setBillTag(bill, tagCode) {
                    const prevTag = bill.tag || this.defaultTag || 'OK';
                    bill.tag = tagCode;
                    bill.display_tag = this.getTagDisplayLabel(tagCode);
                    bill.full_tag = this.getTagFullLabel(tagCode);

                    const abortKey = `tag_${bill.ca_number}`;
                    this.abortInFlight(abortKey);
                    const controller = new AbortController();
                    this.inFlightControllers[abortKey] = controller;

                    if (!this.isOnline || !this.isServerReachable) {
                        this.enqueueOfflineAction('tag', {
                            id: bill.id,
                            ca_number: bill.ca_number,
                            billing_month: bill.billing_month || this.selectedMonth,
                            billing_year: bill.billing_year || this.selectedYear,
                            tag: tagCode
                        });
                        this.showToastNotification(
                            '☁️',
                            `Tag for CA ${bill.ca_number} set to ${bill.display_tag} (Offline)`,
                            {
                                kind: 'tag',
                                bill: bill,
                                prevTag: prevTag,
                                newTag: tagCode
                            }
                        );
                        return;
                    }

                    fetch('/bills/tag', {
                        method: 'POST',
                        keepalive: true,
                        signal: controller.signal,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            id: bill.id,
                            ca_number: bill.ca_number,
                            billing_month: bill.billing_month || this.selectedMonth,
                            billing_year: bill.billing_year || this.selectedYear,
                            tag: tagCode
                        })
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Tag save error: (HTTP ' + res.status + ')');
                        return res.json();
                    })
                    .then(json => {
                        if (json.success) {
                            bill._syncError = false;
                            this.syncErrors = this.syncErrors.filter(e => !(e.ca_number === bill.ca_number && e.field === 'Tag'));
                            this.showToastNotification(
                                '🏷️',
                                `Tag for CA ${bill.ca_number} set to ${json.display_tag}`,
                                {
                                    kind: 'tag',
                                    bill: bill,
                                    prevTag: prevTag,
                                    newTag: tagCode
                                }
                            );
                        } else {
                            throw new Error(json.message || 'Server rejected tag');
                        }
                    })
                    .catch(err => {
                        if (err.name === 'AbortError') return;

                        console.warn('Failed to save tag online, handling rollback or offline:', err);

                        if (err.message && (err.message.includes('Failed to fetch') || err.message.includes('NetworkError'))) {
                            this.isServerReachable = false;
                            this.enqueueOfflineAction('tag', {
                                id: bill.id,
                                ca_number: bill.ca_number,
                                billing_month: bill.billing_month || this.selectedMonth,
                                billing_year: bill.billing_year || this.selectedYear,
                                tag: tagCode
                            });
                            this.showToastNotification('☁️', `Tag saved locally for CA ${bill.ca_number} (Connection lost).`);
                            return;
                        }

                        // Roll back tag on server rejection
                        bill.tag = prevTag;
                        bill.display_tag = this.getTagDisplayLabel(prevTag);
                        bill.full_tag = this.getTagFullLabel(prevTag);
                        bill._syncError = true;
                        bill._syncErrorMsg = err.message || 'Failed to save tag';
                        if (!this.syncErrors.some(e => e.ca_number === bill.ca_number && e.field === 'Tag')) {
                            this.syncErrors.push({ ca_number: bill.ca_number, field: 'Tag', msg: bill._syncErrorMsg });
                        }
                        this.showToastNotification('⚠️', `Failed to set tag for CA ${bill.ca_number} (Reverted).`);
                    })
                    .finally(() => {
                        delete this.inFlightControllers[abortKey];
                    });
                },

                getTagByCode(code) {
                    if (!code) code = this.defaultTag || 'OK';
                    return this.availableTags.find(t => t.code.toUpperCase() === String(code).toUpperCase()) || null;
                },

                getTagDisplayLabel(code) {
                    const t = this.getTagByCode(code);
                    return t ? (t.short_label || t.label) : (code || 'OK');
                },

                getTagFullLabel(code) {
                    const t = this.getTagByCode(code);
                    return t ? t.label : (code || 'OK');
                },

                getTagBadgeClass(code) {
                    const t = this.getTagByCode(code);
                    const color = t ? (t.color || 'emerald') : 'emerald';
                    return this.getActivePillClass(color);
                },

                getActivePillClass(color) {
                    switch (color) {
                        case 'emerald': return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 ring-1 ring-emerald-500/30';
                        case 'blue': return 'bg-blue-500/20 text-blue-300 border-blue-500/40 ring-1 ring-blue-500/30';
                        case 'purple': return 'bg-purple-500/20 text-purple-300 border-purple-500/40 ring-1 ring-purple-500/30';
                        case 'amber': return 'bg-amber-500/20 text-amber-300 border-amber-500/40 ring-1 ring-amber-500/30';
                        case 'rose': return 'bg-rose-500/20 text-rose-300 border-rose-500/40 ring-1 ring-rose-500/30';
                        case 'cyan': return 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40 ring-1 ring-cyan-500/30';
                        case 'indigo': return 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40 ring-1 ring-indigo-500/30';
                        default: return 'bg-slate-500/20 text-slate-300 border-slate-500/40 ring-1 ring-slate-500/30';
                    }
                },

                undoLastAction() {
                    if (!this.toast.undoData) return;
                    const data = this.toast.undoData;
                    this.toast.show = false;

                    if (data.kind === 'working_reading') {
                        data.bill.working_reading = data.prevReading;
                        data.bill.is_manual = (data.prevManual !== undefined) ? data.prevManual : true;
                        data.bill.is_projected = !data.bill.is_manual;
                        data.bill.reading_source = data.prevSource || (data.bill.is_manual ? 'manual' : 'auto');
                        this.saveWorkingReading(data.bill, false, data.bill.reading_source);
                        this.showToastNotification('↩', `Restored reading to ${data.prevReading} for CA ${data.bill.ca_number}`, null);
                        return;
                    }

                    if (data.kind === 'tag') {
                        // Revert tag locally
                        data.bill.tag = data.prevTag;
                        data.bill.display_tag = this.getTagDisplayLabel(data.prevTag);
                        data.bill.full_tag = this.getTagFullLabel(data.prevTag);

                        this.showToastNotification(
                            '↩',
                            `Restored tag to ${data.bill.display_tag} for CA ${data.bill.ca_number}`,
                            null
                        );

                        if (!this.isOnline || !this.isServerReachable) {
                            this.enqueueOfflineAction('tag', {
                                id: data.bill.id,
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                tag: data.prevTag
                            });
                            return;
                        }

                        // Revert tag in DB
                        fetch('/bills/tag', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                id: data.bill.id,
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                tag: data.prevTag
                            })
                        }).catch(err => {
                            console.warn('Revert tag online failed, queuing offline:', err);
                            this.enqueueOfflineAction('tag', {
                                id: data.bill.id,
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                tag: data.prevTag
                            });
                        });
                        return;
                    }

                    if (data.kind === 'remark') {
                        // Revert remark locally
                        data.bill.remark = data.prevRemark;
                        data.bill._lastSavedRemark = data.prevRemark;

                        this.showToastNotification(
                            '↩',
                            `Restored previous note for CA ${data.bill.ca_number}`,
                            null
                        );

                        if (!this.isOnline || !this.isServerReachable) {
                            this.enqueueOfflineAction('remark', {
                                id: data.bill.id,
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                remark: data.prevRemark
                            });
                            return;
                        }

                        // Revert remark in DB
                        fetch('/bills/remark', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                remark: data.prevRemark
                            })
                        }).catch(err => {
                            console.warn('Revert remark online failed, queuing offline:', err);
                            this.enqueueOfflineAction('remark', {
                                id: data.bill.id,
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                remark: data.prevRemark
                            });
                        });
                        return;
                    }

                    if (data.kind === 'status') {
                        const statusLabels = {
                            submitted: 'Submitted',
                            critical: 'Critical',
                            doubt: 'Doubt',
                            pending: 'Pending'
                        };

                        // Revert status locally
                        data.bill.review_status = data.prevStatus;

                        // Revert counts
                        if (this.counts[data.newStatus] !== undefined) {
                            this.counts[data.newStatus] = Math.max(0, this.counts[data.newStatus] - 1);
                        }
                        if (this.counts[data.prevStatus] !== undefined) {
                            this.counts[data.prevStatus] = (this.counts[data.prevStatus] || 0) + 1;
                        }

                        // Re-insert if it was filtered out
                        if (data.wasFilteredOut && (this.filterStatus === 'all' || this.filterStatus === data.prevStatus)) {
                            if (data.removedIndex >= 0 && data.removedIndex <= this.items.length) {
                                this.items.splice(data.removedIndex, 0, data.bill);
                            } else {
                                this.items.push(data.bill);
                            }
                            this.pagination.total = (this.pagination.total || 0) + 1;
                        }

                        this.showToastNotification(
                            '↩',
                            `Restored CA ${data.bill.ca_number} back to ${statusLabels[data.prevStatus]}`,
                            null
                        );

                        if (!this.isOnline || !this.isServerReachable) {
                            this.enqueueOfflineAction('status', {
                                id: data.bill.id,
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                status: data.prevStatus
                            });
                            return;
                        }

                        // API call to restore status in DB
                        fetch('/bills/status', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                status: data.prevStatus
                            })
                        }).catch(err => {
                            console.warn('Revert status online failed, queuing offline:', err);
                            this.enqueueOfflineAction('status', {
                                id: data.bill.id,
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                status: data.prevStatus
                            });
                        });
                    }
                },

                nextCard() {
                    // Pre-fetch next chunk if approaching the edge of loaded items
                    if (this.currentCardIndex >= this.items.length - 5 && this.pagination.current_page < this.pagination.last_page && !this.loadingMoreCards) {
                        this.fetchMoreCards();
                    }

                    if (this.currentCardIndex < this.items.length - 1) {
                        this.currentCardIndex++;
                    } else if (this.pagination.current_page < this.pagination.last_page) {
                        // At the last loaded card: await background load and advance smoothly
                        this.fetchMoreCards().then(() => {
                            if (this.currentCardIndex < this.items.length - 1) {
                                this.currentCardIndex++;
                            }
                        });
                    }
                },

                prevCard() {
                    if (this.currentCardIndex > 0) {
                        this.currentCardIndex--;
                    }
                },

                handleTouchEnd(e) {
                    // Do not slide cards if user was pinch-zooming
                    if (this.isPinching) {
                        if (!e.touches || e.touches.length === 0) {
                            this.isPinching = false;
                        }
                        return;
                    }
                    if (e.touches && e.touches.length > 0) {
                        return;
                    }

                    // Do not slide cards if user is highlighting/selecting text on screen
                    const selection = window.getSelection ? window.getSelection().toString() : '';
                    if (selection && selection.trim().length > 0) {
                        return;
                    }

                    const touchEndX = e.changedTouches[0].screenX;
                    const touchEndY = e.changedTouches[0].screenY;
                    const diffX = this.touchStartX - touchEndX;
                    const diffY = (this.touchStartY || touchEndY) - touchEndY;

                    // Only swipe if horizontal drag is deliberate and dominates vertical scroll
                    if (Math.abs(diffX) > 50 && Math.abs(diffX) > Math.abs(diffY) * 1.4) {
                        if (diffX > 0) {
                            this.nextCard(); // Swiped Left -> Next Card
                        } else {
                            this.prevCard(); // Swiped Right -> Prev Card
                        }
                    }
                },

                renderShortcutBadge(shortcut) {
                    if (window.KeyboardShortcuts) {
                        return window.KeyboardShortcuts.renderBadgesHtml(shortcut);
                    }
                    return shortcut || 'Unset';
                },

                openShortcutsModal() {
                    fetch('/user/shortcuts')
                        .then(r => r.json())
                        .then(data => {
                            if (data.shortcuts) this.shortcuts = data.shortcuts;
                            if (data.labels) this.shortcutLabels = data.labels;
                        })
                        .catch(err => console.error(err));
                    this.showShortcutsModal = true;
                    this.rebindingAction = null;
                    this.rebindSession = null;
                },

                startRebind(actionKey) {
                    if (this.rebindSession) {
                        this.rebindSession.cancel();
                    }

                    this.rebindingAction = actionKey;
                    this.rebindDisplay = 'Press any key or combo...';

                    if (window.KeyboardShortcuts) {
                        this.rebindSession = window.KeyboardShortcuts.startRebindSession({
                            onUpdate: (data) => {
                                this.rebindDisplay = data.display;
                            },
                            onComplete: (combo) => {
                                this.shortcuts[actionKey] = combo;
                                this.rebindingAction = null;
                                this.rebindSession = null;
                                this.showToastNotification('⌨️', `Key for ${this.shortcutLabels[actionKey] || actionKey} set to: ${combo}`, null);
                            },
                            onCancel: () => {
                                this.rebindingAction = null;
                                this.rebindSession = null;
                            }
                        });
                    }
                },

                cancelRebind() {
                    if (this.rebindSession) {
                        this.rebindSession.cancel();
                    }
                    this.rebindingAction = null;
                    this.rebindSession = null;
                },

                saveCustomShortcuts() {
                    fetch('/user/shortcuts', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken()
                        },
                        body: JSON.stringify({ shortcuts: this.shortcuts })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (data.shortcuts) this.shortcuts = data.shortcuts;
                            this.showShortcutsModal = false;
                            this.showToastNotification('✅', 'Custom keyboard shortcuts saved!', null);
                        }
                    })
                    .catch(err => console.error(err));
                },

                resetToDefaults() {
                    fetch('/user/shortcuts/reset', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken()
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (data.shortcuts) this.shortcuts = data.shortcuts;
                            this.showToastNotification('🔄', 'Shortcuts reset to system defaults', null);
                        }
                    })
                    .catch(err => console.error(err));
                },

                onKeyNav(e) {
                    // Quick cheat-sheet overlay with '?' key (when not inside input)
                    if ((e.key === '?' || (e.shiftKey && e.key === '/')) && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                        if (!this.showCreateMruModal && !this.showExistingMruPopup && !this.showNewCycleModal && !this.showPdfViewerModal && !this.showQuickPullModal) {
                            e.preventDefault();
                            this.openShortcutsModal();
                            return;
                        }
                    }

                    if (this.showFieldDeskModal && (e.key === 'Escape' || e.key === 'Esc')) {
                        e.preventDefault();
                        this.showFieldDeskModal = false;
                        return;
                    }

                    // Do nothing if any modal is open or rebinding
                    if (this.showShortcutsModal || this.showCreateMruModal || this.showExistingMruPopup || this.showNewCycleModal || this.showPdfViewerModal || this.showQuickPullModal || this.showTuningModal || this.showMeterHistoryModal || this.showMobileModal || this.showBulkMobileModal || this.showFieldDeskModal || this.rebindingAction) return;

                    // Alt+F: Global shortcut to open FieldDesk quick bridge for current bill
                    if (e.altKey && (e.key === 'f' || e.key === 'F')) {
                        e.preventDefault();
                        const currentBill = this.items[this.currentCardIndex] || this.items[0];
                        if (currentBill) {
                            this.openFieldDeskModal(currentBill);
                        }
                        return;
                    }

                    // If typing inside an input/textarea
                    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                        const exitShortcut = this.shortcuts.exit_box || 'Escape';
                        const isExit = window.KeyboardShortcuts ? window.KeyboardShortcuts.matches(e, exitShortcut) : (e.key === 'Escape');

                        // 1. Exit / Blur input on Escape or configured Exit Key
                        if (isExit || e.key === 'Escape') {
                            e.preventDefault();
                            e.target.blur();
                            this.showToastNotification('↩️', 'Exited input field (Keyboard shortcuts active)', null);
                            return;
                        }

                        // 2. For Remark textarea: Ctrl+Enter or Cmd+Enter saves and exits
                        if (e.target.tagName === 'TEXTAREA' && (e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                            e.preventDefault();
                            const currentBill = this.items[this.currentCardIndex];
                            if (currentBill) {
                                this.saveBillRemark(currentBill, true);
                            }
                            e.target.blur();
                            this.showToastNotification('💾', 'Remark saved & exited', null);
                            return;
                        }

                        return;
                    }

                    if (this.viewMode !== 'card' || this.items.length === 0) return;

                    const currentBill = this.items[this.currentCardIndex];
                    if (!currentBill) return;

                    const ks = window.KeyboardShortcuts;

                    // 0. Undo last action via Ctrl+Z / Cmd+Z
                    if ((e.ctrlKey || e.metaKey) && (e.key === 'z' || e.key === 'Z')) {
                        if (this.toast.show && this.toast.undoData) {
                            e.preventDefault();
                            this.undoLastAction();
                            return;
                        }
                    }

                    // 1. Copy CA Number
                    if (ks ? ks.matches(e, this.shortcuts.copy_ca) : (e.key === this.shortcuts.copy_ca)) {
                        e.preventDefault();
                        this.copyText(currentBill.ca_number, currentBill.id);
                        return;
                    }

                    // 2. Submit / OK
                    if (ks ? ks.matches(e, this.shortcuts.submit_ok) : (e.key === this.shortcuts.submit_ok)) {
                        e.preventDefault();
                        const wasFilteredOut = this.updateBillStatus(currentBill, 'submitted');
                        if (!wasFilteredOut) {
                            this.nextCard();
                        }
                        return;
                    }

                    // 3. Mark Doubt
                    if (ks ? ks.matches(e, this.shortcuts.mark_doubt) : (e.key === this.shortcuts.mark_doubt)) {
                        e.preventDefault();
                        const wasFilteredOut = this.updateBillStatus(currentBill, 'doubt');
                        if (!wasFilteredOut && this.filterStatus === 'all') {
                            this.nextCard();
                        }
                        return;
                    }

                    // 4. Mark Critical
                    if (ks ? ks.matches(e, this.shortcuts.mark_critical) : (e.key === this.shortcuts.mark_critical)) {
                        e.preventDefault();
                        const wasFilteredOut = this.updateBillStatus(currentBill, 'critical');
                        if (!wasFilteredOut && this.filterStatus === 'all') {
                            this.nextCard();
                        }
                        return;
                    }

                    // Unmodified ArrowUp / ArrowDown are reserved exclusively for natural vertical page scrolling
                    if (!e.ctrlKey && !e.altKey && !e.metaKey && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
                        return; // Let browser scroll naturally
                    }

                    // 5. Next Card (Configured shortcut OR un-modified ArrowRight)
                    const isNextArrow = !e.ctrlKey && !e.altKey && !e.metaKey && (e.key === 'ArrowRight');
                    if ((ks && ks.matches(e, this.shortcuts.next_card)) || isNextArrow) {
                        e.preventDefault();
                        this.nextCard();
                        return;
                    }

                    // 6. Previous Card (Configured shortcut OR un-modified ArrowLeft)
                    const isPrevArrow = !e.ctrlKey && !e.altKey && !e.metaKey && (e.key === 'ArrowLeft');
                    if ((ks && ks.matches(e, this.shortcuts.prev_card)) || isPrevArrow) {
                        e.preventDefault();
                        this.prevCard();
                        return;
                    }

                    // 7. Focus / Edit Working Reading
                    if (ks ? ks.matches(e, this.shortcuts.focus_reading) : (e.key === this.shortcuts.focus_reading)) {
                        e.preventDefault();
                        if (this.isBillLocked(currentBill)) {
                            this.showToastNotification('🔒', 'Bill is submitted and locked. Click unlock to edit.');
                            return;
                        }
                        const el = document.getElementById('working-reading-input-' + currentBill.id);
                        if (el) {
                            el.focus();
                            el.select();
                        }
                        return;
                    }

                    // 8. Auto-Fill Working Reading (Prev + Avg)
                    if (ks ? ks.matches(e, this.shortcuts.auto_fill_reading) : (e.key === this.shortcuts.auto_fill_reading)) {
                        e.preventDefault();
                        if (this.isBillLocked(currentBill)) {
                            this.showToastNotification('🔒', 'Cannot auto-fill: Bill is submitted and locked. Unlock first.');
                            return;
                        }
                        this.autoFillWorkingReading(currentBill);
                        return;
                    }

                    // 9. Open / Focus Remark
                    if (ks ? ks.matches(e, this.shortcuts.open_remark) : (e.key === this.shortcuts.open_remark)) {
                        e.preventDefault();
                        const el = document.getElementById('remark-input-' + currentBill.id);
                        if (el) {
                            el.focus();
                            el.select();
                        }
                        return;
                    }
                },

                openCreateMruModal() {
                    this.newMruCode = '';
                    this.newMruName = '';
                    this.newMruIdentifier = '';
                    this.createMruError = null;
                    this.mruOverageRequired = false;
                    this.mruOverageAmount = 0;
                    this.mruOverageWalletBalance = 0;
                    this.mruOverageInsufficient = false;
                    this.mruOverageMessage = '';
                    this.mruTopupUrl = cfg.mruTopupUrl || '/wallet';
                    this.mruUpgradeUrl = cfg.mruUpgradeUrl || '/user-panel/subscription';
                    this.showCreateMruModal = true;
                },

                submitCreateMru(payOverage = false) {
                    if (!this.newMruCode.trim() || !this.newMruName.trim()) return;

                    this.isSubmittingMru = true;
                    this.createMruError = null;

                    fetch('/mrus', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
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
                            this.mruTopupUrl = data.topup_url || cfg.mruTopupUrl || '/wallet';
                            this.mruUpgradeUrl = data.upgrade_url || cfg.mruUpgradeUrl || '/user-panel/subscription';
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
                            this.showCreateMruModal = false;
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

                exportCsv() {
                    const url = new URL('/bills/export-csv', window.location.origin);
                    url.searchParams.append('month', this.selectedMonth);
                    url.searchParams.append('year', this.selectedYear);
                    if (this.filterMru) url.searchParams.append('mru_id', this.filterMru);
                    if (this.filterStatus && this.filterStatus !== 'all') url.searchParams.append('filter', this.filterStatus);
                    if (this.searchQuery) url.searchParams.append('search', this.searchQuery);

                    window.location.href = url.toString();
                },

                exportZip() {
                    const url = new URL('/bills/export-zip', window.location.origin);
                    url.searchParams.append('month', this.selectedMonth);
                    url.searchParams.append('year', this.selectedYear);
                    if (this.filterMru) url.searchParams.append('mru_id', this.filterMru);
                    if (this.filterStatus && this.filterStatus !== 'all') url.searchParams.append('filter', this.filterStatus);
                    if (this.searchQuery) url.searchParams.append('search', this.searchQuery);

                    window.location.href = url.toString();
                },

                copyText(text, billId = null) {
                    if (!text) return;
                    const self = this;
                    const onCopied = () => {
                        if (billId) {
                            self.copiedCaId = billId;
                            if (self.copiedCaTimeout) clearTimeout(self.copiedCaTimeout);
                            self.copiedCaTimeout = setTimeout(() => {
                                self.copiedCaId = null;
                            }, 2000);
                        }
                        self.showToastNotification('📋', `Copied CA: ${text}`, null);
                    };

                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text)
                            .then(onCopied)
                            .catch(err => {
                                console.warn('Clipboard API failed, using fallback:', err);
                                self.fallbackCopyText(text, onCopied);
                            });
                    } else {
                        self.fallbackCopyText(text, onCopied);
                    }
                },

                fallbackCopyText(text, callback) {
                    try {
                        const textArea = document.createElement("textarea");
                        textArea.value = text;
                        textArea.style.position = "fixed";
                        textArea.style.top = "-9999px";
                        textArea.style.left = "-9999px";
                        textArea.style.opacity = "0";
                        textArea.setAttribute("readonly", "");
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        textArea.setSelectionRange(0, 99999);
                        const successful = document.execCommand('copy');
                        document.body.removeChild(textArea);
                        if (successful) {
                            if (callback) callback();
                        } else {
                            window.prompt("Copy CA number (Ctrl+C or tap-and-hold):", text);
                        }
                    } catch (err) {
                        console.error('Fallback copy exception:', err);
                        window.prompt("Copy CA number (Ctrl+C or tap-and-hold):", text);
                    }
                },

                formatNumber(val) {
                    if (!val) return '0';
                    return Number(val).toLocaleString();
                },

                formatCurrency(val) {
                    if (val === null || val === undefined || val === '' || isNaN(Number(val))) return 'N/A';
                    const num = Number(val);
                    if (num < 0) {
                        return '-₹' + Math.abs(num).toFixed(2);
                    }
                    return '₹' + num.toFixed(2);
                },

                getAmountStyle(amount) {
                    const cleanAmt = typeof amount === 'string' ? amount.replace(/[^0-9.-]+/g, '') : amount;
                    const val = Number(cleanAmt) || 0;
                    if (!this.colorSettings || this.colorSettings.enabled === false) {
                        if (val < 0) return 'text-emerald-500 font-bold';
                        return val > 0 ? 'text-blue-600 dark:text-cyan-400 font-bold' : 'text-slate-500 dark:text-slate-400 font-bold';
                    }

                    const safe = Number(this.colorSettings.amount_safe_ceiling ?? 500);
                    const warning = Number(this.colorSettings.amount_warning_ceiling ?? 1500);
                    const danger = Number(this.colorSettings.amount_danger_floor ?? 2500);

                    // Negative / Credit (< ₹0): Emerald Green
                    if (val < 0) {
                        return 'text-emerald-500 font-bold';
                    }

                    // ₹0 to Safe Ceiling: Vibrant Green (Safe Zone)
                    if (val <= safe) {
                        return 'text-emerald-500 font-bold';
                    }

                    // Safe Ceiling to Warning Ceiling: Lime / Amber (Normal Zone)
                    if (val <= warning) {
                        const mid = (safe + warning) / 2;
                        return val <= mid ? 'text-lime-500 font-bold' : 'text-amber-500 font-bold';
                    }

                    // Warning Ceiling to Danger Floor: Warm Orange (Elevated Zone)
                    if (val < danger) {
                        return 'text-orange-500 font-extrabold';
                    }

                    // Danger Floor+: Bold Crimson Red (High Attention Zone)
                    return 'text-rose-600 font-black';
                },

                getAvgUnitStyle(units, type = 'text') {
                    const cleanUnits = typeof units === 'string' ? units.replace(/[^0-9.-]+/g, '') : units;
                    const u = Number(cleanUnits) || 0;
                    if (!this.colorSettings || this.colorSettings.enabled === false) {
                        if (type === 'border') return 'border-slate-200/80 dark:border-slate-700';
                        if (type === 'badge') return 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300';
                        return 'text-slate-800 dark:text-white font-bold';
                    }

                    const safe = Number(this.colorSettings.units_safe_ceiling ?? 50);
                    const warning = Number(this.colorSettings.units_warning_ceiling ?? 120);
                    const danger = Number(this.colorSettings.units_danger_floor ?? 200);

                    if (type === 'border') {
                        if (u <= safe) {
                            return 'border-emerald-500/50 dark:border-emerald-600/50';
                        }
                        if (u <= warning) {
                            const mid = (safe + warning) / 2;
                            return u <= mid ? 'border-lime-500/50 dark:border-lime-600/50' : 'border-amber-500/50 dark:border-amber-600/50';
                        }
                        if (u < danger) {
                            return 'border-orange-500/50 dark:border-orange-600/50';
                        }
                        return 'border-rose-600/70 dark:border-rose-500/70 ring-1 ring-rose-500/30';
                    }

                    if (type === 'badge') {
                        if (u <= safe) {
                            return 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20';
                        }
                        if (u <= warning) {
                            const mid = (safe + warning) / 2;
                            return u <= mid ? 'bg-lime-500/10 text-lime-500 border border-lime-500/20' : 'bg-amber-500/10 text-amber-500 border border-amber-500/20';
                        }
                        if (u < danger) {
                            return 'bg-orange-500/10 text-orange-500 border border-orange-500/20';
                        }
                        return 'bg-rose-500/10 text-rose-600 border border-rose-500/20';
                    }

                    // Reading numbers spectrum
                    if (u <= safe) {
                        return 'text-emerald-500 font-bold';
                    }
                    if (u <= warning) {
                        const mid = (safe + warning) / 2;
                        return u <= mid ? 'text-lime-500 font-bold' : 'text-amber-500 font-bold';
                    }
                    if (u < danger) {
                        return 'text-orange-500 font-extrabold';
                    }
                    return 'text-rose-600 font-black';
                }
            };
        }