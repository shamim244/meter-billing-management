{{-- Dedicated Reading Gap Forensic Dossier Header --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('reading-gap.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 dark:text-cyan-400 hover:underline transition">
                <span>←</span> Reading Gap Hub
            </a>
            <span class="text-slate-400 dark:text-slate-600">/</span>
            <span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">{{ $account->ca_number }}</span>
            <span class="text-slate-400 dark:text-slate-600">•</span>
            <a href="{{ route('consumers.show', $account->ca_number) }}" class="text-xs font-bold text-slate-500 hover:text-indigo-400 transition flex items-center gap-1">
                <span>👤</span> 360 Consumer Page →
            </a>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>⚖️</span>
                <span>{{ $account->consumer_name ?: 'Consumer Gap Audit' }}</span>
            </h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono bg-brand-500/10 text-brand-600 dark:text-cyan-400 border border-brand-500/20">
                CA: {{ $account->ca_number }}
            </span>
            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                Meter: {{ $account->meter_no ?: 'N/A' }}
            </span>
            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                MRU: {{ $account->mru ? $account->mru->code : 'GENERAL' }}
            </span>
            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                Tariff: {{ $account->tariff_category ?: 'DS1D' }}
            </span>
            
            {{-- Big Status Badge --}}
            @if(($gapSummary['gap_status'] ?? '') === 'under_posted')
                <span class="px-3 py-1 rounded-xl text-xs font-black bg-rose-500/15 text-rose-500 border border-rose-500/30 flex items-center gap-1.5">
                    <span>🚨</span> Under-Posted (+{{ number_format(abs($gapSummary['net_gap'] ?? 0)) }} kWh)
                </span>
            @elseif(($gapSummary['gap_status'] ?? '') === 'over_posted')
                <span class="px-3 py-1 rounded-xl text-xs font-black bg-amber-500/15 text-amber-500 border border-amber-500/30 flex items-center gap-1.5">
                    <span>⚠️</span> Over-Posted (-{{ number_format(abs($gapSummary['net_gap'] ?? 0)) }} kWh)
                </span>
            @elseif(($gapSummary['gap_status'] ?? '') === 'in_sync')
                <span class="px-3 py-1 rounded-xl text-xs font-black bg-emerald-500/15 text-emerald-500 border border-emerald-500/30 flex items-center gap-1.5">
                    <span>✅</span> In-Sync (0 kWh Gap)
                </span>
            @else
                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-slate-500/15 text-slate-400 border border-slate-500/30 flex items-center gap-1.5">
                    <span>ℹ️</span> Uninspected (No Physical Reading)
                </span>
            @endif
        </div>
    </div>

    <!-- Quick Action Controls -->
    <div class="flex items-center gap-2 flex-wrap">
        <button type="button" @click="openInspectionModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition active:scale-95 cursor-pointer">
            <span>+</span> Log Physical Visit
        </button>
        <button type="button" @click="openMeterSwapModal()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition cursor-pointer">
            <span>🔄</span> Swap Meter
        </button>
    </div>
</div>
