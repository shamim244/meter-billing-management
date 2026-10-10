<!-- Connection Test & Proceed Buttons -->
<div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-800">
    <div class="flex items-center gap-2 w-full sm:w-auto">
        <a href="{{ route('install.step1') }}" class="px-4 py-2.5 text-xs font-bold text-slate-400 hover:text-white bg-slate-950 hover:bg-slate-800 rounded-xl transition">
            ← Back
        </a>
        <button type="button" @click="testConnection()" :disabled="testing" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl transition flex items-center gap-2">
            <span x-show="testing" class="animate-spin text-indigo-400">⏳</span>
            <span x-text="testing ? 'Testing...' : '🔌 Test Connection'"></span>
        </button>
    </div>

    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2">
        <span>Save & Continue</span>
        <span>→</span>
    </button>
</div>
