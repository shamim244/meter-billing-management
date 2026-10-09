{{-- Header & Action --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
            <a href="{{ route('admin.notifications.email_providers.index') }}" class="hover:text-white transition">Notifications</a>
            <span>/</span>
            <span class="text-indigo-400">Hostinger Mailbox Hub</span>
        </div>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>📫</span> Live Hostinger Mailbox Inspector
        </h1>
        <p class="text-sm text-slate-400 mt-1">
            Real-time two-way inbox inspector, delivery confirmation, and direct outgoing mail console powered by Hostinger Mail REST API.
        </p>
    </div>

    <div class="flex items-center gap-3">
        <button @click="openComposeModal()" class="px-4 py-2.5 bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 text-slate-950 font-black rounded-xl text-xs transition flex items-center gap-2 shadow-lg shadow-cyan-500/20">
            <span>⚡</span> Compose Email
        </button>
    </div>
</div>
