/**
 * Admin Reading Gap & Unbilled Energy Intelligence Alpine Controller
 */
function adminReadingGapApp() {
    return {
        loading: false,
        kpis: {},
        mruList: [],
        inspections: [],

        init() {
            this.fetchData();
        },

        async fetchData() {
            this.loading = true;
            try {
                const config = window.adminReadingGapConfig || {};
                const res = await fetch(config.dataUrl || '/admin/reading-gap/data', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.kpis = data.kpis || {};
                    this.mruList = data.mru_breakdown || [];
                    this.inspections = data.recent_inspections || [];
                }
            } catch (err) {
                console.error('Error fetching admin reading gap data:', err);
            } finally {
                this.loading = false;
            }
        }
    };
}

window.adminReadingGapApp = adminReadingGapApp;
document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        window.Alpine.data('adminReadingGapApp', adminReadingGapApp);
    }
});
