{{-- Flash Alerts --}}
@if(session('error'))
    <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-xs text-rose-300">
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-xs text-emerald-300">
        {{ session('success') }}
    </div>
@endif
