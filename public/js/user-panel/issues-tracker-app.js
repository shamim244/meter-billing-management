/**
 * User Panel Issues Tracker Alpine.js Application
 * Handles ticket lookup by code, modal viewing, and clipboard actions
 */
function userIssuesTracker(config) {
    config = config || {};
    var trackBaseUrl = config.trackUrl || '/issues/track';

    return {
        lookupCode: '',
        isSearching: false,
        lookupResult: null,
        lookupError: null,
        detailsModalOpen: false,
        selectedIssue: null,
        toastMessage: null,

        lookupTicket() {
            if (!this.lookupCode) return;
            this.isSearching = true;
            this.lookupError = null;
            this.lookupResult = null;

            fetch(trackBaseUrl + '/' + encodeURIComponent(this.lookupCode.trim()), {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                this.isSearching = false;
                if (data.success && data.issue) {
                    this.lookupResult = data.issue;
                } else {
                    this.lookupError = data.message || 'No issue found with this reference code.';
                }
            })
            .catch(e => {
                this.isSearching = false;
                this.lookupError = 'Network error while checking ticket. Please try again.';
            });
        },

        viewIssueDetails(issue) {
            this.selectedIssue = issue;
            this.detailsModalOpen = true;
        },

        openNewReportModal() {
            window.dispatchEvent(new CustomEvent('open-bug-reporter'));
        },

        copyText(text) {
            if (!text) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            }
            this.toastMessage = 'Copied ' + text + ' to clipboard!';
            setTimeout(() => {
                this.toastMessage = null;
            }, 3000);
        }
    };
}

window.userIssuesTracker = userIssuesTracker;
