<!-- Top Back Navigation -->
<div class="flex items-center justify-between">
    <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition">
        <span>←</span> Back to User Dossier
    </a>

    <div class="text-xs text-slate-400 font-mono">
        User ID: #{{ $user->id }}
    </div>
</div>
