<div class="flex items-center justify-between">
    <a href="{{ route('admin.issues.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-indigo-400 hover:text-indigo-300 transition">
        ← Back to Bug Tracker Desk
    </a>
    <div class="text-xs font-mono text-slate-500">Ticket: <span class="font-bold text-slate-300">{{ $issue->issue_code }}</span></div>
</div>
