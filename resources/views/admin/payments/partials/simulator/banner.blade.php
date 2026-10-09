{{-- Banner Info & Quick Seed Action --}}
<div class="p-5 rounded-2xl bg-indigo-950/40 border border-indigo-500/30 text-xs text-indigo-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="space-y-1">
        <div class="font-bold text-white flex items-center gap-2 text-sm">
            <span>🧪</span> Developer Sandbox & Gateway Testing Console
        </div>
        <p class="text-slate-400 text-xs">
            Test end-to-end payment flows, mock checkout success/failures, dispatch HMAC-signed webhooks, and populate verification queues without live API credentials.
        </p>
    </div>

    <!-- Quick Seed Action Button -->
    <form action="{{ route('admin.payments.simulator.seed') }}" method="POST" class="shrink-0">
        @csrf
        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 flex items-center gap-2">
            <span>✨</span> Populate Sample Demo Records
        </button>
    </form>
</div>
