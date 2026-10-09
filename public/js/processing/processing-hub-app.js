/**
 * Data Processing Center Hub App Logic
 * Decoupled client-side Alpine component for Bill Downloader and PDF Parser Hub
 */
function processingHubApp() {
    const config = window.processingConfig || {};

    return {
        mruPeriodsMap: config.mruPeriodsMap || {},
        selectedMruId: config.selectedMruId || '',
        selectedPeriodKey: config.selectedPeriodKey || '',
        selectedMonth: config.selectedMonth || (new Date().getMonth() + 1),
        selectedYear: config.selectedYear || new Date().getFullYear(),
        searchQuery: '',
        activeLogFilter: 'all',
        autoScroll: true,

        // Modal state
        showCycleModal: false,
        modalMruId: config.selectedMruId || '',
        modalMonth: new Date().getMonth() + 1,
        modalYear: new Date().getFullYear(),
        cycleInProgress: false,
        cycleResult: null,

        stats: {
            total_cas: 0,
            downloaded_count: 0,
            missing_downloads: 0,
            failed_count: 0,
            pdf_bills_count: 0,
            parsed_count: 0,
            pending_parse: 0,
            download_percent: 0,
            parse_percent: 0,
            failed_bills: [],
        },

        downloaderRunning: false,
        parserRunning: false,
        pipelineRunning: false,
        logLines: [],
        logPollInterval: null,

        get availablePeriods() {
            if (!this.selectedMruId) return [];
            return this.mruPeriodsMap[this.selectedMruId] || [];
        },

        init() {
            // Check if current period key matches available periods
            const periods = this.availablePeriods;
            if (periods.length > 0) {
                const matching = periods.find(p => p.key === this.selectedPeriodKey);
                if (!matching) {
                    this.selectedPeriodKey = periods[0].key;
                    this.selectedMonth = periods[0].month;
                    this.selectedYear = periods[0].year;
                }
            } else {
                this.selectedPeriodKey = 'custom';
            }

            // Initial fetch
            this.fetchStatus();
            this.fetchLogs();

            // Auto-pause polling when tab hidden
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopPolling();
                } else if (this.isAnyTaskRunning()) {
                    this.startPolling();
                }
            });
        },

        isAnyTaskRunning() {
            return this.downloaderRunning || this.parserRunning || this.pipelineRunning || this.cycleInProgress;
        },

        startPolling() {
            if (this.logPollInterval) return;
            this.logPollInterval = setInterval(() => {
                this.fetchLogs();
                this.fetchStatus();
                if (!this.isAnyTaskRunning()) {
                    this.stopPolling();
                }
            }, 1500);
        },

        stopPolling() {
            if (this.logPollInterval) {
                clearInterval(this.logPollInterval);
                this.logPollInterval = null;
            }
        },

        get filteredLogLines() {
            let lines = this.logLines;
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                lines = lines.filter(l => l.toLowerCase().includes(q));
            }
            return lines;
        },

        onMruChange() {
            this.modalMruId = this.selectedMruId;
            const periods = this.availablePeriods;
            if (periods.length > 0) {
                this.selectedPeriodKey = periods[0].key;
                this.selectedMonth = periods[0].month;
                this.selectedYear = periods[0].year;
            } else {
                this.selectedPeriodKey = 'custom';
                this.selectedMonth = new Date().getMonth() + 1;
                this.selectedYear = new Date().getFullYear();
            }
            this.fetchStatus();
        },

        onPeriodKeyChange() {
            if (this.selectedPeriodKey === 'custom') {
                return;
            }
            const parts = this.selectedPeriodKey.split('_');
            if (parts.length === 2) {
                this.selectedMonth = parseInt(parts[0], 10);
                this.selectedYear = parseInt(parts[1], 10);
                this.fetchStatus();
            }
        },

        onCustomDateChange() {
            this.selectedPeriodKey = `${this.selectedMonth}_${this.selectedYear}`;
            this.fetchStatus();
        },

        getCurrentPeriodLabel() {
            const months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${months[this.selectedMonth] || ''} ${this.selectedYear}`;
        },

        getDashboardUrl() {
            return `/dashboard?mru_id=${this.selectedMruId}&month=${this.selectedMonth}&year=${this.selectedYear}`;
        },

        openNewCycleForCurrentMru() {
            this.modalMruId = this.selectedMruId;
            this.modalMonth = this.selectedMonth;
            this.modalYear = this.selectedYear;
            this.cycleResult = null;
            this.showCycleModal = true;
        },

        launchBillingCycle(actionType = 'download_all') {
            if (!this.modalMruId) return;

            this.cycleInProgress = true;
            this.cycleResult = null;

            fetch('/mrus/billing-cycle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || ''
                },
                body: JSON.stringify({
                    mru_id: this.modalMruId,
                    billing_month: this.modalMonth,
                    billing_year: this.modalYear,
                    action_type: actionType
                })
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Error creating cycle');
                return data;
            })
            .then(json => {
                this.cycleResult = json;
                if (json.success) {
                    setTimeout(() => {
                        window.location.href = `/processing?mru_id=${this.modalMruId}&month=${this.modalMonth}&year=${this.modalYear}`;
                    }, 800);
                }
            })
            .catch(err => {
                this.cycleResult = {
                    success: false,
                    message: err.message || 'An error occurred while creating cycle.'
                };
            })
            .finally(() => {
                this.cycleInProgress = false;
            });
        },

        fetchStatus() {
            const url = new URL('/processing/status', window.location.origin);
            if (this.selectedMruId) url.searchParams.append('mru_id', this.selectedMruId);
            url.searchParams.append('month', this.selectedMonth);
            url.searchParams.append('year', this.selectedYear);

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.stats) {
                        this.stats = data.stats;
                    }
                })
                .catch(err => console.error(err));
        },

        fetchLogs() {
            fetch('/processing/logs')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.logs !== undefined) {
                        const raw = data.logs.trim();
                        if (raw) {
                            this.logLines = raw.split('\n');
                            if (this.autoScroll) {
                                this.$nextTick(() => {
                                    const el = document.getElementById('consoleTerminalBody');
                                    if (el) el.scrollTop = el.scrollHeight;
                                });
                            }
                        }
                    }
                })
                .catch(err => console.error(err));
        },

        runDownloader(mode = 'all', explicitCas = null) {
            this.downloaderRunning = true;
            this.startPolling();

            const modeLabel = explicitCas ? `Specific CAs (${explicitCas.length})` : mode;
            this.logLines.push(`[${new Date().toLocaleTimeString()}] 🚀 Launching Bill Downloader (Mode: ${modeLabel})...`);

            return fetch('/processing/downloader', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    mru_id: this.selectedMruId || null,
                    billing_month: this.selectedMonth,
                    billing_year: this.selectedYear,
                    mode: mode,
                    ca_numbers: explicitCas
                })
            })
            .then(r => r.json())
            .then(data => {
                this.downloaderRunning = false;
                this.stopPolling();
                this.fetchStatus();
                this.fetchLogs();
                return data;
            })
            .catch(err => {
                this.downloaderRunning = false;
                this.stopPolling();
                this.logLines.push(`[${new Date().toLocaleTimeString()}] ❌ Downloader request error.`);
                this.fetchStatus();
                this.fetchLogs();
                throw err;
            });
        },

        runParser(mode = 'pending_only') {
            this.parserRunning = true;
            this.startPolling();

            this.logLines.push(`[${new Date().toLocaleTimeString()}] 🚀 Launching PDF Parser & Extractor (Mode: ${mode})...`);

            return fetch('/processing/parser', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    mru_id: this.selectedMruId || null,
                    billing_month: this.selectedMonth,
                    billing_year: this.selectedYear,
                    mode: mode
                })
            })
            .then(r => r.json())
            .then(data => {
                this.parserRunning = false;
                this.stopPolling();
                this.fetchStatus();
                this.fetchLogs();
                return data;
            })
            .catch(err => {
                this.parserRunning = false;
                this.stopPolling();
                this.logLines.push(`[${new Date().toLocaleTimeString()}] ❌ Parser request error.`);
                this.fetchStatus();
                this.fetchLogs();
                throw err;
            });
        },

        // ⚡ 1-Click Pipeline: Downloader -> Parser -> Ledger Sync
        runFullPipeline() {
            this.pipelineRunning = true;
            this.startPolling();

            this.logLines.push(`[${new Date().toLocaleTimeString()}] ⚡ === STARTING FULL AUTO-PIPELINE ===`);

            this.runDownloader('missing_only')
                .then(() => {
                    return this.runParser('all');
                })
                .then(() => {
                    this.pipelineRunning = false;
                    this.stopPolling();
                    this.logLines.push(`[${new Date().toLocaleTimeString()}] 🏆 === PIPELINE COMPLETED SUCCESSFULLY ===`);
                    this.fetchStatus();
                    this.fetchLogs();
                })
                .catch(() => {
                    this.pipelineRunning = false;
                    this.stopPolling();
                    this.logLines.push(`[${new Date().toLocaleTimeString()}] ⚠️ Pipeline interrupted with exceptions.`);
                    this.fetchStatus();
                    this.fetchLogs();
                });
        },

        copyLogs() {
            const text = this.filteredLogLines.join('\n');
            navigator.clipboard.writeText(text).then(() => {
                alert('Filtered logs copied to clipboard!');
            });
        },

        clearConsoleScreen() {
            this.logLines = [];
        },

        clearLogFile() {
            fetch('/processing/logs/clear', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(() => {
                this.logLines = [];
            });
        }
    };
}
