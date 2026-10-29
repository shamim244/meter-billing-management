<x-guest-layout>
    <div class="w-full max-w-2xl mx-auto py-8">
        @if (session('error'))
            <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 sm:p-10 shadow-2xl relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-60 h-60 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-60 h-60 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col items-center text-center">
                <div class="w-20 h-20 bg-gradient-to-tr from-emerald-500 to-teal-400 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/20 mb-6 border border-emerald-400/30">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>

                <span class="px-3.5 py-1 text-xs font-semibold rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 uppercase tracking-wider mb-3">
                    Official Android Companion App
                </span>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-3">
                    NBPDCL Field Reader
                </h1>

                <p class="text-slate-400 text-sm sm:text-base max-w-lg mb-6 leading-relaxed">
                    Ultra-fast offline billing, consumer ledger search, and 0ms PDF bill viewer designed specifically for zero-connectivity rural zones.
                </p>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 w-full mb-8">
                    <div class="p-3.5 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex flex-col items-center text-center">
                        <span class="text-xs text-slate-400">Offline Mode</span>
                        <span class="text-sm font-bold text-slate-200 mt-1">100% Standalone</span>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex flex-col items-center text-center">
                        <span class="text-xs text-slate-400">Bill Viewer</span>
                        <span class="text-sm font-bold text-slate-200 mt-1">0ms Instant PDF</span>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex flex-col items-center text-center">
                        <span class="text-xs text-slate-400">Search Speed</span>
                        <span class="text-sm font-bold text-slate-200 mt-1">&lt; 5ms SQLite</span>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex flex-col items-center text-center">
                        <span class="text-xs text-slate-400">Synchronization</span>
                        <span class="text-sm font-bold text-slate-200 mt-1">1-Tap Outbox</span>
                    </div>
                </div>

                <div class="w-full flex flex-col sm:flex-row gap-3 justify-center items-center">
                    <a href="{{ route('download.app.apk') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-white font-bold text-base shadow-lg shadow-emerald-500/25 transition-all duration-200 hover:scale-[1.02]">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Download APK (v{{ $version }})</span>
                    </a>
                </div>

                <div class="flex items-center justify-center gap-4 text-xs text-slate-400 mt-4">
                    <span>Size: {{ $fileSizeMb > 0 ? $fileSizeMb.' MB' : 'Release Build' }}</span>
                    <span>•</span>
                    <span>Android 8.0+</span>
                    @if ($modifiedAt)
                        <span>•</span>
                        <span>Updated: {{ $modifiedAt }}</span>
                    @endif
                </div>

                <div class="mt-8 pt-6 border-t border-slate-800/80 w-full text-left">
                    <h3 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Installation Guide</h3>
                    <ol class="text-xs text-slate-400 space-y-1 list-decimal list-inside leading-relaxed">
                        <li>Download the APK file to your Android phone.</li>
                        <li>Tap the downloaded file and choose <strong>Install</strong>.</li>
                        <li>If prompted, allow <em>"Install unknown apps"</em> for your browser.</li>
                        <li>Launch <strong>NBPDCL Field Reader</strong> and sign in using your portal credentials or API token.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
