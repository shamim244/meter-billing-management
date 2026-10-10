<!-- 1. Python ADB Tool Endpoints -->
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
    <div>
        <div class="flex items-center justify-between mb-3">
            <span class="w-8 h-8 rounded-xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center font-bold text-sm border border-emerald-500/30">🤖</span>
            <span class="text-[10px] font-mono" :class="featureAutomation ? 'text-emerald-400' : 'text-slate-500'" x-text="featureAutomation ? 'ENABLED' : 'PAUSED'"></span>
        </div>
        <h4 class="text-sm font-bold text-white">Python ADB Automation Tool</h4>
        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
            Controls external ADB reading agents querying queue and updating verification status.
        </p>
        <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
            <div>/api/v1/automation/queue</div>
            <div>/api/v1/automation/update-status</div>
        </div>
    </div>
    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-300">Automation Access</span>
        <button type="button" @click="featureAutomation = !featureAutomation" class="relative inline-flex items-center cursor-pointer">
            <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="featureAutomation ? 'bg-emerald-600' : 'bg-slate-800'">
                <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="featureAutomation ? 'translate-x-5' : 'translate-x-0.5'"></div>
            </div>
        </button>
    </div>
</div>

<!-- 2. Mobile Offline Sync -->
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
    <div>
        <div class="flex items-center justify-between mb-3">
            <span class="w-8 h-8 rounded-xl bg-blue-500/15 text-blue-400 flex items-center justify-center font-bold text-sm border border-blue-500/30">📱</span>
            <span class="text-[10px] font-mono" :class="featureMobileSync ? 'text-emerald-400' : 'text-slate-500'" x-text="featureMobileSync ? 'ENABLED' : 'PAUSED'"></span>
        </div>
        <h4 class="text-sm font-bold text-white">Flutter Mobile Offline Sync</h4>
        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
            Allows field mobile readers to download MRU packages and batch-sync offline meter records.
        </p>
        <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
            <div>/api/v1/sync/mrus/{id}/download</div>
            <div>/api/v1/sync/readings/batch</div>
        </div>
    </div>
    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-300">Mobile Sync Access</span>
        <button type="button" @click="featureMobileSync = !featureMobileSync" class="relative inline-flex items-center cursor-pointer">
            <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="featureMobileSync ? 'bg-emerald-600' : 'bg-slate-800'">
                <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="featureMobileSync ? 'translate-x-5' : 'translate-x-0.5'"></div>
            </div>
        </button>
    </div>
</div>

<!-- 3. Bulk Batch Sync -->
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
    <div>
        <div class="flex items-center justify-between mb-3">
            <span class="w-8 h-8 rounded-xl bg-purple-500/15 text-purple-400 flex items-center justify-center font-bold text-sm border border-purple-500/30">📦</span>
            <span class="text-[10px] font-mono" :class="featureBatchSync ? 'text-emerald-400' : 'text-slate-500'" x-text="featureBatchSync ? 'ENABLED' : 'PAUSED'"></span>
        </div>
        <h4 class="text-sm font-bold text-white">Bulk Batch Uploads</h4>
        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
            Controls multi-record mass uploads. Useful to pause during heavy server maintenance.
        </p>
        <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
            <div>/api/v1/bills/batch-sync</div>
            <div>Payloads up to 100 records</div>
        </div>
    </div>
    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-300">Batch Upload Access</span>
        <button type="button" @click="featureBatchSync = !featureBatchSync" class="relative inline-flex items-center cursor-pointer">
            <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="featureBatchSync ? 'bg-emerald-600' : 'bg-slate-800'">
                <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="featureBatchSync ? 'translate-x-5' : 'translate-x-0.5'"></div>
            </div>
        </button>
    </div>
</div>
