@if(session('status'))
    <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-800/60 text-emerald-300 text-xs font-semibold flex items-center gap-3">
        <span class="text-base">✅</span>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-800/60 text-rose-300 text-xs font-semibold space-y-1">
        <div class="font-bold flex items-center gap-2">
            <span>⚠️</span>
            <span>Please correct the errors below:</span>
        </div>
        <ul class="list-disc list-inside pl-4 text-[11px] text-rose-400">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif
