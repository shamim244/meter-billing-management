<!-- 4. Direct Consumer Updates -->
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
    <div>
        <div class="flex items-center justify-between mb-3">
            <span class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-400 flex items-center justify-center font-bold text-sm border border-amber-500/30">✍️</span>
            <span class="text-[10px] font-mono" :class="featureConsumerUpdates ? 'text-emerald-400' : 'text-slate-500'" x-text="featureConsumerUpdates ? 'ENABLED' : 'PAUSED'"></span>
        </div>
        <h4 class="text-sm font-bold text-white">Single Reading Updates</h4>
        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
            Direct consumer reading adjustment endpoint from third-party webhook integrations.
        </p>
        <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
            <div>POST /api/v1/consumers/reading</div>
        </div>
    </div>
    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-300">Single Update Access</span>
        <button type="button" @click="featureConsumerUpdates = !featureConsumerUpdates" class="relative inline-flex items-center cursor-pointer">
            <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="featureConsumerUpdates ? 'bg-emerald-600' : 'bg-slate-800'">
                <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="featureConsumerUpdates ? 'translate-x-5' : 'translate-x-0.5'"></div>
            </div>
        </button>
    </div>
</div>

<!-- 5. User Self-Service Key Creation -->
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
    <div>
        <div class="flex items-center justify-between mb-3">
            <span class="w-8 h-8 rounded-xl bg-indigo-500/15 text-indigo-400 flex items-center justify-center font-bold text-sm border border-indigo-500/30">🔑</span>
            <span class="text-[10px] font-mono" :class="userKeys ? 'text-emerald-400' : 'text-slate-500'" x-text="userKeys ? 'ALLOWED' : 'LOCKED'"></span>
        </div>
        <h4 class="text-sm font-bold text-white">User Panel Key Generation</h4>
        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
            When enabled, standard users and agents can generate API keys directly in their User Control Panel.
        </p>
        <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
            <div>/user-panel/api-keys</div>
        </div>
    </div>
    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-300">Allow Key Creation</span>
        <button type="button" @click="userKeys = !userKeys" class="relative inline-flex items-center cursor-pointer">
            <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="userKeys ? 'bg-emerald-600' : 'bg-slate-800'">
                <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="userKeys ? 'translate-x-5' : 'translate-x-0.5'"></div>
            </div>
        </button>
    </div>
</div>

<!-- 6. Public Docs & OpenAPI Spec -->
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
    <div>
        <div class="flex items-center justify-between mb-3">
            <span class="w-8 h-8 rounded-xl bg-teal-500/15 text-teal-400 flex items-center justify-center font-bold text-sm border border-teal-500/30">📖</span>
            <span class="text-[10px] font-mono" :class="publicDocs ? 'text-emerald-400' : 'text-slate-500'" x-text="publicDocs ? 'PUBLIC' : 'HIDDEN'"></span>
        </div>
        <h4 class="text-sm font-bold text-white">Public Developer Portal & OpenAPI</h4>
        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
            Controls public availability of the OpenAPI 3.0 schema and interactive Developer Portal.
        </p>
        <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
            <div>/docs/api</div>
            <div>/api/v1/openapi.json</div>
        </div>
    </div>
    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-300">Public Docs Access</span>
        <button type="button" @click="publicDocs = !publicDocs" class="relative inline-flex items-center cursor-pointer">
            <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="publicDocs ? 'bg-emerald-600' : 'bg-slate-800'">
                <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="publicDocs ? 'translate-x-5' : 'translate-x-0.5'"></div>
            </div>
        </button>
    </div>
</div>
