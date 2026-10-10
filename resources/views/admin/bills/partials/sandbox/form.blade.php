<div class="border-b border-slate-800/80 pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <div>
        <h2 class="text-base font-bold text-white flex items-center gap-2">
            <span>🧪</span>
            <span>Live Engine & Extraction Diagnostic Sandbox</span>
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">
            Test live download connectivity and PDF layout extraction in-flight without altering any database records.
        </p>
    </div>
    <span class="text-[11px] text-amber-400 bg-amber-500/10 px-3 py-1 rounded-xl border border-amber-500/20 font-medium">
        Read-Only Diagnostic Test
    </span>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div>
        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Sample CA Number</label>
        <input type="text" x-model="diagCa" placeholder="e.g. 10230041576" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Test Driver</label>
        <select x-model="diagDriver" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
            <option value="auto">Auto (WSS with Legacy Fallback)</option>
            <option value="wss">WSS FluentGrid Only</option>
            <option value="legacy">Legacy BSPHCL ASMX Only</option>
        </select>
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Billing Month</label>
        <select x-model="diagMonth" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ (int)date('n') === $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
            @endfor
        </select>
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Billing Year</label>
        <input type="number" x-model="diagYear" value="{{ (int)date('Y') }}" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
    </div>
</div>

<div class="flex justify-end">
    <button type="button" @click="runDiagnostic()" :disabled="diagLoading" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-lg transition flex items-center gap-2">
        <svg x-show="diagLoading" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
        <span x-text="diagLoading ? 'Testing Live Endpoints...' : '🚀 Run Live Diagnostic'"></span>
    </button>
</div>
