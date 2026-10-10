<!-- Driver Selection -->
<div>
    <label class="block text-xs font-bold text-slate-300 mb-1.5">Database Driver</label>
    <div class="grid grid-cols-2 gap-3">
        <button type="button" @click="driver = 'mysql'" :class="driver === 'mysql' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 font-bold' : 'bg-slate-950/60 border-slate-800 text-slate-400'" class="p-3 rounded-xl border text-xs text-center transition">
            MySQL / MariaDB (Recommended)
        </button>
        <button type="button" @click="driver = 'sqlite'" :class="driver === 'sqlite' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 font-bold' : 'bg-slate-950/60 border-slate-800 text-slate-400'" class="p-3 rounded-xl border text-xs text-center transition">
            SQLite (File-based)
        </button>
    </div>
    <input type="hidden" name="driver" :value="driver">
</div>
