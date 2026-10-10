<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>📑</span> Notification Templates & Priority Mapping
        </h1>
        <p class="text-sm text-slate-400 mt-1">
            Manage message copies, priority routing rules, and merge placeholders for all system events.
        </p>
    </div>

    <div class="flex items-center gap-3">
        <form method="POST" action="{{ route('admin.notifications.templates.reset') }}" onsubmit="return confirm('Reset all notification templates to factory defaults?');">
            @csrf
            <button type="submit" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-2">
                <span>🔄</span> Reset to Defaults
            </button>
        </form>
    </div>
</div>
