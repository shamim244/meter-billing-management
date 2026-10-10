<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>🏷️</span> Bill Review Tags Manager
        </h1>
        <p class="text-sm text-slate-400 mt-1">Configure available tags, display labels, badge colors, and the default tag for consumer review cards.</p>
    </div>
    <div class="flex items-center gap-3">
        <button type="button" @click="showNewTagModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-indigo-600/30">
            <span>➕</span> Add Custom Tag
        </button>
        <form method="POST" action="{{ route('admin.tags.reset_factory') }}" onsubmit="return confirm('Reset all bill tags to factory defaults?');">
            @csrf
            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700/60">
                🔄 Factory Reset
            </button>
        </form>
    </div>
</div>
