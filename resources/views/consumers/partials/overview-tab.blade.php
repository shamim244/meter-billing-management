{{-- Profile & Technical Specifications Tab --}}
<div x-show="activeTab === 'overview'" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Consumer Details Card -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4 shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Account Information</h3>
                <button type="button" @click="showEditProfileModal = true" class="text-xs font-bold text-brand-600 dark:text-cyan-400 hover:underline flex items-center gap-1 cursor-pointer">
                    <span>✏️</span> Edit Profile
                </button>
            </div>
            <div class="grid grid-cols-2 gap-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Consumer Name:</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ $account->consumer_name ?: '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Father / Husband Name:</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $account->father_name ?: '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Mobile Number:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-white">{{ $account->mobile ?: 'Not registered' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Address / Village:</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $account->address ?: '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">MRU Area:</span>
                    <span class="font-bold font-mono text-brand-600 dark:text-cyan-400">{{ $account->mru ? $account->mru->code.' ('.$account->mru->name.')' : 'Unassigned' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">GPS Coordinates:</span>
                    @if($account->map_link)
                        <a href="{{ $account->map_link }}" target="_blank" rel="noopener" class="text-brand-600 dark:text-cyan-400 hover:underline flex items-center gap-1 font-mono">
                            <span>📍</span> Open Google Maps
                        </a>
                    @else
                        <span class="text-slate-400 italic">Not tagged</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Meter & Technical Specs Card -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4 shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Technical Specifications</h3>
                <button type="button" @click="openMeterSwapModal()" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 cursor-pointer">
                    <span>🔄</span> Meter Swap
                </button>
            </div>
            <div class="grid grid-cols-2 gap-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Meter Serial No:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-white">{{ $account->meter_no ?: '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Tariff Category:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-white">{{ $account->tariff_category ?: 'DS1D' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Billing Basis:</span>
                    <span class="font-bold font-mono px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">{{ $account->billing_basis ?: 'OK' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Baseline Initial Reading:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-white">{{ $account->baseline_previous_reading ?? $account->last_working_reading ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Safe Monthly Ceiling:</span>
                    <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400">{{ $gapSummary['safe_ceiling'] }} kWh</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Gap Recovery Mode:</span>
                    <span class="font-bold font-mono text-slate-800 dark:text-slate-200">{{ $account->gap_recovery_mode ?: 'Auto Standard' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
