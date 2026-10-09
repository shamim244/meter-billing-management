<!-- 2. Purge Analytics Confirmation Modal -->
<div x-show="confirmClearAnalytics" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div @click.away="confirmClearAnalytics = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="w-12 h-12 rounded-2xl bg-amber-500/15 text-amber-400 flex items-center justify-center font-bold text-xl border border-amber-500/30 mx-auto">
            🧹
        </div>
        <div class="text-center">
            <h3 class="text-lg font-bold text-white">Clear Traffic Analytics?</h3>
            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                This will delete historical request telemetry logs. Traffic statistics will restart from zero.
            </p>
        </div>
        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" @click="confirmClearAnalytics = false" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 rounded-xl transition">
                Cancel
            </button>
            <form action="{{ route('admin.api_hub.analytics.clear') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-500 rounded-xl transition shadow-lg shadow-amber-600/30">
                    Clear Analytics
                </button>
            </form>
        </div>
    </div>
</div>
