{{-- Top Header & Actions --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2">
            <span>💳</span> Payment Gateway & Verification Queue
        </h1>
        <p class="text-xs text-slate-400 mt-1">
            Manage online PG transactions, manual UPI & bank transfer verification queues.
        </p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.payments.settings') }}" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold border border-slate-700 transition flex items-center gap-2 shadow-sm">
            <span>⚙️</span> Payment Settings & Gateways
        </a>
    </div>
</div>
