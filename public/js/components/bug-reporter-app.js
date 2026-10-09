/**
 * Bug Reporter Component Logic
 * Decoupled client-side Alpine component for bug reporting, issue tracking, and diagnostics.
 */
function bugReporterComponent() {
    return {
        isOpen: false,
        activeTab: 'report',
        isSubmitting: false,
        submittedCode: null,
        errorMessage: null,
        recentConsoleErrors: [],

        // Ticket tracking state
        trackCode: '',
        isTrackLoading: false,
        trackResult: null,
        trackError: null,
        recentTickets: [],

        // My reports state
        isMyReportsLoading: false,
        myReportsList: [],
        myReportsCount: 0,

        form: {
            title: '',
            description: '',
            category: 'other',
            severity: 'medium',
            page_url: '',
            route_name: '',
            ca_number: null,
            mru_id: null,
            billing_month: null,
            billing_year: null,
        },

        init() {
            // Load recent ticket codes from localStorage
            try {
                const stored = localStorage.getItem('nbpdcl_recent_tickets');
                if (stored) {
                    this.recentTickets = JSON.parse(stored) || [];
                }
            } catch(e) {}

            // Global Keyboard Shortcut: Ctrl + Shift + B opens Bug Reporter
            window.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.shiftKey && (e.key === 'B' || e.key === 'b')) {
                    e.preventDefault();
                    this.openModal('report');
                }
            });

            // Capture unhandled console error logs in memory
            const originalConsoleError = console.error;
            console.error = (...args) => {
                try {
                    this.recentConsoleErrors.push({
                        time: new Date().toISOString(),
                        message: args.map(a => typeof a === 'object' ? JSON.stringify(a) : String(a)).join(' ')
                    });
                    if (this.recentConsoleErrors.length > 5) this.recentConsoleErrors.shift();
                } catch(e) {}
                originalConsoleError.apply(console, args);
            };

            // Listen for custom trigger from any card or table row
            window.addEventListener('open-bug-reporter', (event) => {
                this.openModal('report', event.detail || {});
            });
        },

        openModal(tab = 'report', context = {}) {
            this.activeTab = tab;
            this.captureContext(context);
            this.submittedCode = null;
            this.errorMessage = null;
            this.isOpen = true;

            if (tab === 'my_reports') {
                this.loadMyReports();
            }
        },

        closeModal() {
            this.isOpen = false;
        },

        resetForm() {
            this.submittedCode = null;
            this.form.title = '';
            this.form.description = '';
            this.form.category = 'other';
            this.form.severity = 'medium';
            this.captureContext();
        },

        captureContext(override = {}) {
            this.form.page_url = window.location.href;
            
            const urlParams = new URLSearchParams(window.location.search);
            this.form.mru_id = override.mru_id || urlParams.get('mru_id') || localStorage.getItem('dashboard_mru') || null;
            this.form.billing_month = override.billing_month || urlParams.get('month') || null;
            this.form.billing_year = override.billing_year || urlParams.get('year') || null;
            this.form.ca_number = override.ca_number || null;

            if (window.location.pathname.includes('/dashboard')) {
                this.form.category = 'calculation';
            } else if (window.location.pathname.includes('/mrus')) {
                this.form.category = 'mru_sync';
            } else if (window.location.pathname.includes('/wallet') || window.location.pathname.includes('/payments')) {
                this.form.category = 'wallet_payment';
            }
        },

        saveRecentTicket(code) {
            if (!code) return;
            if (!this.recentTickets.includes(code)) {
                this.recentTickets.unshift(code);
                if (this.recentTickets.length > 6) this.recentTickets.pop();
                try {
                    localStorage.setItem('nbpdcl_recent_tickets', JSON.stringify(this.recentTickets));
                } catch(e) {}
            }
        },

        submitReport() {
            this.isSubmitting = true;
            this.errorMessage = null;

            const payload = {
                ...this.form,
                client_context: {
                    user_agent: navigator.userAgent,
                    screen: `${window.screen.width}x${window.screen.height}`,
                    viewport: `${window.innerWidth}x${window.innerHeight}`,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    online: navigator.onLine,
                    console_errors: this.recentConsoleErrors,
                }
            };

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch('/issues/report', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.isSubmitting = false;
                if (data.success) {
                    this.submittedCode = data.issue_code;
                    this.saveRecentTicket(data.issue_code);
                } else {
                    this.errorMessage = data.message || 'Failed to submit issue report.';
                }
            })
            .catch(err => {
                this.isSubmitting = false;
                this.errorMessage = 'Network error while submitting report. Please check your connection.';
            });
        },

        trackSubmittedTicket() {
            if (!this.submittedCode) return;
            this.trackCode = this.submittedCode;
            this.activeTab = 'track';
            this.trackTicket();
        },

        trackTicket() {
            if (!this.trackCode) return;
            this.isTrackLoading = true;
            this.trackError = null;
            this.trackResult = null;

            fetch('/issues/track/' + encodeURIComponent(this.trackCode.trim()), {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                this.isTrackLoading = false;
                if (data.success && data.issue) {
                    this.trackResult = data.issue;
                    this.saveRecentTicket(data.issue.issue_code);
                } else {
                    this.trackError = data.message || 'No issue found with this reference code.';
                }
            })
            .catch(err => {
                this.isTrackLoading = false;
                this.trackError = 'Network error while checking ticket. Please try again.';
            });
        },

        switchTabToMyReports() {
            this.activeTab = 'my_reports';
            this.loadMyReports();
        },

        loadMyReports() {
            this.isMyReportsLoading = true;
            fetch('/issues/my-reports', {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                this.isMyReportsLoading = false;
                if (data.success && data.issues) {
                    this.myReportsList = data.issues;
                    this.myReportsCount = data.issues.length;
                }
            })
            .catch(err => {
                this.isMyReportsLoading = false;
            });
        },

        copyText(text) {
            if (!text) return;
            navigator.clipboard.writeText(text);
        }
    };
}
