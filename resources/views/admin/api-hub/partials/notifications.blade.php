<!-- Status Notification Banner -->
@if (session('status'))
    <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs shadow-lg flex items-center justify-between gap-3 animate-fade-in">
        <div class="flex items-center gap-2.5">
            <span class="text-base">✅</span>
            <span class="font-semibold">{{ session('status') }}</span>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs shadow-lg space-y-1">
        <div class="font-bold flex items-center gap-2 text-rose-200">
            <span>⚠️</span> Validation Errors:
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-rose-300/90 pl-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
