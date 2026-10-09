<!-- MARKET STANDARD ONE-TIME SECRET REVEAL BANNER -->
@if (session('new_api_key'))
    <div class="p-6 sm:p-8 rounded-3xl bg-amber-500/10 border-2 border-amber-500/40 dark:border-amber-500/50 shadow-2xl relative overflow-hidden">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl shrink-0 font-bold">
                ⚠️
            </div>
            <div class="space-y-4 flex-1">
                <div>
                    <h3 class="text-base font-extrabold text-amber-900 dark:text-amber-300">
                        Save Your Secret API Key Now!
                    </h3>
                    <p class="text-xs text-amber-800 dark:text-amber-400/90 mt-1">
                        For your security, this secret token will <strong>never be shown again</strong>. Copy and store it immediately in your Python script or password manager.
                    </p>
                </div>

                <!-- Key Details & Code Display Box -->
                <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-amber-500/30 dark:border-slate-800 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-inner">
                    <div class="font-mono text-xs font-bold text-slate-900 dark:text-cyan-300 select-all break-all pr-2">
                        {{ session('new_api_key')['plain_text_token'] }}
                    </div>
                    <button @click="copySecret('{{ session('new_api_key')['plain_text_token'] }}')"
                            type="button"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-black shrink-0 transition-colors shadow-sm cursor-pointer">
                        <span x-show="!copied">📋 Copy Key</span>
                        <span x-show="copied" x-cloak class="text-emerald-950 font-black">✓ Copied!</span>
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-600 dark:text-slate-400">
                    <div><strong>Name:</strong> {{ session('new_api_key')['name'] }}</div>
                    <div>•</div>
                    <div><strong>Prefix:</strong> <code class="font-mono text-cyan-600 dark:text-cyan-400">{{ session('new_api_key')['key_prefix'] }}...</code></div>
                    <div>•</div>
                    <div><strong>Expires:</strong> {{ session('new_api_key')['expires_at'] }}</div>
                </div>
            </div>
        </div>
    </div>
@endif
