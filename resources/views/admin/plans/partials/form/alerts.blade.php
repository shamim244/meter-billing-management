@if(isset($plan))
    <div class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-2xl text-amber-300 text-xs font-medium flex items-start gap-2.5">
        <span class="text-base">🔒</span>
        <div>
            <strong class="font-bold">Important Plan Edit Invariant:</strong>
            Updating these plan parameters or rates will only apply to new subscriber registrations and future renewals.
            Existing active subscriber quotas and pricing snapshots remain <strong>immutable</strong> until their renewal cycle.
        </div>
    </div>
@endif

@if($errors->any())
    <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-300 text-xs space-y-1">
        <div class="font-bold flex items-center gap-1.5">
            <span>⚠️</span> Please correct the following errors:
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-400">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif
