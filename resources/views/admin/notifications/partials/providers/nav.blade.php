{{-- Sub-Nav Links --}}
<div class="flex items-center gap-2">
    <a href="{{ route('admin.notifications.email_providers.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-md shadow-indigo-600/20">
        Email Providers
    </a>
    <a href="{{ route('admin.notifications.templates.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
        Notification Templates
    </a>
    <a href="{{ route('admin.notifications.failed_queue') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
        Failed Critical Queue
    </a>
</div>
