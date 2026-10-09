<!-- Error Alerts -->
<div x-show="errorMessage" x-cloak class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
    <span x-text="'❌ ' + errorMessage"></span>
    <button type="button" @click="errorMessage = null" class="text-rose-600 dark:text-rose-400 font-bold">✕</button>
</div>

@if($errors->any())
    <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold space-y-1">
        <div class="font-bold">❌ Please correct the following errors:</div>
        <ul class="list-disc list-inside pl-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(isset($downgradeEligibility) && !$downgradeEligibility['eligible'])
    <div class="p-5 rounded-3xl bg-amber-50 dark:bg-amber-950/50 border-2 border-amber-300 dark:border-amber-800 space-y-4">
        <div class="flex items-start gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">Active MRU Quota Exceeded for this Plan</h3>
                <p class="text-xs text-amber-800 dark:text-amber-300 mt-1">
                    The target plan (<strong>{{ $plan->name }}</strong>) includes <strong>{{ $downgradeEligibility['new_plan_quota'] }}</strong> MRU(s). You currently have <strong>{{ $downgradeEligibility['active_mrus_count'] }}</strong> active MRU workspaces.
                    Please lock or delete <strong>{{ $downgradeEligibility['excess_mrus'] }}</strong> MRU(s) to proceed with this plan downgrade.
                </p>
            </div>
        </div>

        <!-- Quick Lock Buttons for Active MRUs -->
        <div class="space-y-2 pt-3 border-t border-amber-200 dark:border-amber-800">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                Lock an MRU workspace to free quota:
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($downgradeEligibility['active_mrus'] as $mruItem)
                    <div class="flex items-center justify-between p-3 rounded-2xl bg-white dark:bg-slate-900 border border-amber-200 dark:border-slate-800 shadow-xs">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-lg text-xs font-mono font-bold bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-cyan-300 border border-blue-200 dark:border-blue-800">{{ $mruItem->code }}</span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $mruItem->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('mrus.lock', $mruItem) }}" onsubmit="return confirm('Lock MRU {{ $mruItem->code }} to free quota?');">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition flex items-center gap-1 shadow-xs">
                                <span>🔒 Lock</span>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="pt-2 border-t border-amber-200 dark:border-amber-800 flex items-center justify-between">
            <span class="text-xs text-slate-500">Need to manage or delete MRUs?</span>
            <a href="{{ route('mrus.index') }}" class="text-xs font-bold text-blue-600 dark:text-cyan-400 hover:underline flex items-center gap-1">
                <span>🗂️ Go to MRU Workspace Management →</span>
            </a>
        </div>
    </div>
@endif
