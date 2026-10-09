/**
 * NBPDCL SaaS Pro - Marketing Landing Page Alpine.js Application
 * Handles Interactive 4-Box Reading Calculator Simulator & ROI Calculator
 */
function marketingApp() {
    return {
        // 4-Box Reading Demo State
        demoPrev: 500,
        demoBasis: 'OK',
        demoPdf: 800,
        demoAvg: 50,
        demoWorking: 550,
        demoSyncLabel: '⚡ Auto-Projected',
        demoSyncColor: 'text-emerald-400',

        // ROI Calculator State
        mruCount: 4,
        consumersPerMru: 1500,

        init() {
            this.recalculateDemo();
        },

        recalculateDemo() {
            if (this.demoBasis === 'MD') {
                this.demoAvg = 76;
            } else if (this.demoBasis === 'LK') {
                this.demoAvg = 35;
            } else if (this.demoBasis === 'PL') {
                this.demoAvg = 60;
            } else {
                this.demoAvg = 50;
            }

            let projected = (this.demoPrev || 0) + this.demoAvg;
            let pdfVal = this.demoPdf || 0;

            // Invariant: Working >= PDF
            if (pdfVal > 0 && projected < pdfVal) {
                projected = pdfVal;
            }
            this.demoWorking = projected;

            if (pdfVal > 0) {
                if (this.demoWorking > pdfVal) {
                    let delta = this.demoWorking - pdfVal;
                    this.demoSyncLabel = '+' + delta + ' kWh Ahead of PDF';
                    this.demoSyncColor = 'text-emerald-400';
                } else if (this.demoWorking === pdfVal) {
                    this.demoSyncLabel = '✓ Exact Match with PDF';
                    this.demoSyncColor = 'text-cyan-300';
                } else {
                    this.demoSyncLabel = '⚠️ Clamped to PDF Invariant';
                    this.demoSyncColor = 'text-rose-400';
                }
            } else {
                this.demoSyncLabel = '⚡ Projected from Avg';
                this.demoSyncColor = 'text-slate-400';
            }
        }
    };
}

window.marketingApp = marketingApp;
