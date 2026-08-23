<x-user-panel-layout>
    <x-slot name="header">
        General & Workspace Preferences
    </x-slot>

    <div class="space-y-8">
        <!-- Top Overview -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-brand-50 dark:bg-brand-950/80 text-brand-700 dark:text-cyan-300 border border-brand-200/60 dark:border-brand-800/60">
                            Customization
                        </span>
                        <span class="text-xs text-slate-400">•</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">NBPDCL Billing Workspace</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Workspace Preferences</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                        Fine-tune your default dashboard layout, pagination density, auto-fill behaviors, and visual appearance.
                    </p>
                </div>
            </div>
        </div>

        <!-- Preferences Form -->
        <form method="POST" action="{{ route('user-panel.preferences.update') }}" class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl space-y-6">
            @csrf

            <!-- 1. Default Review Mode -->
            <div class="space-y-2 border-b border-slate-100 dark:border-slate-800 pb-6">
                <label class="text-sm font-bold text-slate-900 dark:text-white block">Default Dashboard Review Mode</label>
                <p class="text-xs text-slate-500 dark:text-slate-400">Choose the view layout loaded by default when you open the working dashboard.</p>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <label class="p-4 rounded-2xl border cursor-pointer flex items-center gap-3 transition {{ ($preferences['default_view'] ?? 'card') === 'card' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-900 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
                        <input type="radio" name="default_view" value="card" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['default_view'] ?? 'card') === 'card' ? 'checked' : '' }}>
                        <div>
                            <div class="text-xs font-bold flex items-center gap-1.5">
                                <span>🗃️ Card View (Recommended)</span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Focuses on one consumer card at a time with hands-on-keyboard shortcuts.</div>
                        </div>
                    </label>

                    <label class="p-4 rounded-2xl border cursor-pointer flex items-center gap-3 transition {{ ($preferences['default_view'] ?? 'card') === 'table' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-900 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
                        <input type="radio" name="default_view" value="table" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['default_view'] ?? 'card') === 'table' ? 'checked' : '' }}>
                        <div>
                            <div class="text-xs font-bold flex items-center gap-1.5">
                                <span>📋 Table View</span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Spreadsheet-style dense listing for reviewing multiple consumers simultaneously.</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 2. Default Page Size -->
            <div class="space-y-2 border-b border-slate-100 dark:border-slate-800 pb-6">
                <label class="text-sm font-bold text-slate-900 dark:text-white block">Default Page Size</label>
                <p class="text-xs text-slate-500 dark:text-slate-400">Number of consumer records loaded per batch.</p>
                
                <div class="flex items-center gap-3 pt-2">
                    @foreach([25, 50, 100] as $size)
                        <label class="px-5 py-3 rounded-2xl border cursor-pointer flex items-center gap-2 text-xs font-bold transition {{ ($preferences['default_page_size'] ?? 50) == $size ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
                            <input type="radio" name="default_page_size" value="{{ $size }}" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['default_page_size'] ?? 50) == $size ? 'checked' : '' }}>
                            <span>{{ $size }} bills</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- 3. Card View Density & Spacing -->
            <div class="space-y-2 border-b border-slate-100 dark:border-slate-800 pb-6">
                <label class="text-sm font-bold text-slate-900 dark:text-white block">Card View Density & Spacing</label>
                <p class="text-xs text-slate-500 dark:text-slate-400">Control padding and layout compactness during card review.</p>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <label class="p-4 rounded-2xl border cursor-pointer flex items-center gap-3 transition {{ ($preferences['card_density'] ?? 'compact') === 'compact' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-900 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
                        <input type="radio" name="card_density" value="compact" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['card_density'] ?? 'compact') === 'compact' ? 'checked' : '' }}>
                        <div>
                            <div class="text-xs font-bold flex items-center gap-1.5">
                                <span>⚡ Compact / Standard (Recommended)</span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Reduced gaps and optimal spacing designed to fit on standard laptop screens without scrolling.</div>
                        </div>
                    </label>

                    <label class="p-4 rounded-2xl border cursor-pointer flex items-center gap-3 transition {{ ($preferences['card_density'] ?? 'compact') === 'comfortable' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-900 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
                        <input type="radio" name="card_density" value="comfortable" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['card_density'] ?? 'compact') === 'comfortable' ? 'checked' : '' }}>
                        <div>
                            <div class="text-xs font-bold flex items-center gap-1.5">
                                <span>📐 Comfortable / Spacious</span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Generous padding with relaxed visual spacing.</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 4. Amount Text Size in Card View -->
            <div class="space-y-2 border-b border-slate-100 dark:border-slate-800 pb-6">
                <label class="text-sm font-bold text-slate-900 dark:text-white block">Card Amount Font Size</label>
                <p class="text-xs text-slate-500 dark:text-slate-400">Scale the total billing amount font size in the middle banner.</p>
                
                <div class="flex items-center gap-3 pt-2">
                    <label class="px-5 py-3 rounded-2xl border cursor-pointer flex items-center gap-2 text-xs font-bold transition {{ ($preferences['amount_size'] ?? 'standard') === 'standard' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
                        <input type="radio" name="amount_size" value="standard" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['amount_size'] ?? 'standard') === 'standard' ? 'checked' : '' }}>
                        <span>Standard Clean (Balanced)</span>
                    </label>

                    <label class="px-5 py-3 rounded-2xl border cursor-pointer flex items-center gap-2 text-xs font-bold transition {{ ($preferences['amount_size'] ?? 'standard') === 'large' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
                        <input type="radio" name="amount_size" value="large" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['amount_size'] ?? 'standard') === 'large' ? 'checked' : '' }}>
                        <span>Large / Extra Prominent</span>
                    </label>
                </div>
            </div>

            <!-- 5. Smart Auto-Fill & Automation -->
            <div class="space-y-3 border-b border-slate-100 dark:border-slate-800 pb-6">
                <label class="text-sm font-bold text-slate-900 dark:text-white block">Automation & Remark Preferences</label>

                <div class="space-y-2.5">
                    <label class="flex items-start gap-3 p-4 rounded-2xl bg-slate-50/50 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800 cursor-pointer">
                        <input type="checkbox" name="auto_fill_suggestion" value="1" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500" {{ ($preferences['auto_fill_suggestion'] ?? true) ? 'checked' : '' }}>
                        <div>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Auto-Suggest Projected Working Readings</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Automatically suggest `Previous Reading + Avg kWh` projection for unreviewed bills while strictly enforcing `Reading >= PDF Reading`.</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 rounded-2xl bg-slate-50/50 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800 cursor-pointer">
                        <input type="checkbox" name="sound_feedback" value="1" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500" {{ ($preferences['sound_feedback'] ?? true) ? 'checked' : '' }}>
                        <div>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Audio & Toast Notifications on Rapid Actions</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Display floating toast notifications with 1-click Undo whenever an audit status is marked or changed.</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-4 rounded-2xl bg-slate-50/50 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800 cursor-pointer">
                        <input type="checkbox" name="show_remark_presets" value="1" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500" {{ ($preferences['show_remark_presets'] ?? false) ? 'checked' : '' }}>
                        <div>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Show Quick Preset Pills under Remark Box</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Display quick-insert button pills (e.g. "Door Locked", "Meter Burnt") below the Remark box. Default is hidden for a clean, minimal card.</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 4. Appearance / Theme -->
            <div class="space-y-2 pb-2">
                <label class="text-sm font-bold text-slate-900 dark:text-white block">Color Theme Preference</label>
                <p class="text-xs text-slate-500 dark:text-slate-400">Select your preferred visual theme for the entire portal.</p>
                
                <div class="grid grid-cols-3 gap-3 pt-2">
                    <label class="p-4 rounded-2xl border cursor-pointer text-center transition {{ ($preferences['theme'] ?? 'system') === 'light' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
                        <input type="radio" name="theme" value="light" class="sr-only" {{ ($preferences['theme'] ?? 'system') === 'light' ? 'checked' : '' }}>
                        <div class="text-lg mb-1">☀️</div>
                        <div class="text-xs font-bold">Light Mode</div>
                    </label>

                    <label class="p-4 rounded-2xl border cursor-pointer text-center transition {{ ($preferences['theme'] ?? 'system') === 'dark' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
                        <input type="radio" name="theme" value="dark" class="sr-only" {{ ($preferences['theme'] ?? 'system') === 'dark' ? 'checked' : '' }}>
                        <div class="text-lg mb-1">🌙</div>
                        <div class="text-xs font-bold">Dark Mode</div>
                    </label>

                    <label class="p-4 rounded-2xl border cursor-pointer text-center transition {{ ($preferences['theme'] ?? 'system') === 'system' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
                        <input type="radio" name="theme" value="system" class="sr-only" {{ ($preferences['theme'] ?? 'system') === 'system' ? 'checked' : '' }}>
                        <div class="text-lg mb-1">💻</div>
                        <div class="text-xs font-bold">System Sync</div>
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-md shadow-brand-500/20 transition">
                    💾 Save Preferences
                </button>
            </div>
        </form>
    </div>
</x-user-panel-layout>
