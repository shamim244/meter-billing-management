<!-- ========================================== -->
<!-- TAB 4: KEY SECURITY POLICIES               -->
<!-- ========================================== -->
<div x-show="activeTab === 'policies'" class="space-y-6" x-cloak>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Max Keys Quota -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-400 flex items-center justify-center font-bold text-lg border border-indigo-500/30">🔢</span>
                <div>
                    <h4 class="text-sm font-bold text-white">Max Active Keys Per User</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Limit the number of active API keys a single user can create.</p>
                </div>
            </div>
            <div class="pt-2">
                <label class="text-xs font-semibold text-slate-300 block mb-1.5">Maximum Key Count</label>
                <input type="number" x-model="maxKeys" min="1" max="50" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
                <span class="text-[10px] text-slate-500 mt-1 block">Default: 5 active keys. Exceeding requests in User Panel are blocked.</span>
            </div>
        </div>

        <!-- Permanent Keys Policy -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4 flex flex-col justify-between">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="w-10 h-10 rounded-2xl bg-cyan-500/15 text-cyan-400 flex items-center justify-center font-bold text-lg border border-cyan-500/30">♾️</span>
                    <div>
                        <h4 class="text-sm font-bold text-white">Allow Non-Expiring Keys</h4>
                        <p class="text-xs text-slate-400 mt-0.5">Allow users to select "Never Expire" when generating API keys.</p>
                    </div>
                </div>
                <button type="button" @click="allowPermanent = !allowPermanent" class="relative inline-flex items-center cursor-pointer">
                    <div class="w-12 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="allowPermanent ? 'bg-emerald-600' : 'bg-slate-800'">
                        <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="allowPermanent ? 'translate-x-6' : 'translate-x-0.5'"></div>
                    </div>
                </button>
            </div>
            <div class="text-[10px] text-slate-500 border-t border-slate-800 pt-3">
                When turned off, users must pick a finite expiration period (1, 7, 30, 90, or 365 days).
            </div>
        </div>
    </div>

    <!-- Save Action Bar -->
    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex items-center justify-between">
        <div class="text-xs text-slate-400">
            Security policies apply immediately when agents create or renew keys.
        </div>
        <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-cyan-400 hover:from-emerald-300 hover:to-cyan-300 shadow-lg shadow-emerald-500/20 transition">
            Save Security Policies
        </button>
    </div>
</div>
