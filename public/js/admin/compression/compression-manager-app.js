function compressionDashboard() {
    const config = window.compressionConfig || {};
    return {
        loading: false,
        benchmark: config.benchmark || { algorithms: {}, original_size_bytes: 0 },
        summary: {
            recommended_for_content: 'zstd',
            fastest_algorithm: 'zstd',
            highest_ratio_algorithm: 'br'
        },
        formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        async runBenchmark(type) {
            this.loading = true;
            try {
                const res = await fetch(config.diagnosticUrl || '/admin/compression/diagnostic', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ type })
                });
                const data = await res.json();
                if (data.success) {
                    this.benchmark = data.benchmark;
                    this.summary = data.summary;
                }
            } catch (e) {
                console.error('Benchmark execution error:', e);
            } finally {
                this.loading = false;
            }
        }
    };
}

if (typeof window !== 'undefined') {
    window.compressionDashboard = compressionDashboard;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('compressionDashboard', compressionDashboard);
        }
    });
}
