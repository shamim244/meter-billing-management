@if ($errors->any())
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-semibold space-y-1">
        <div class="font-bold flex items-center gap-1.5">
            <span>❌</span> Validation Errors:
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-400">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
