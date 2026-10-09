{{-- Status Alerts --}}
@if(session('status'))
    <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-800/60 text-emerald-300 text-xs font-semibold flex items-center gap-3">
        <span class="text-base">✅</span>
        <span>{{ session('status') }}</span>
    </div>
@endif
