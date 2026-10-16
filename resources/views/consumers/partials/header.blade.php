{{-- Dedicated Consumer Hub Header & Navigation --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 mb-2">
            @if(($fromHub ?? null) === 'reading-gap')
                <a href="{{ route('reading-gap.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 dark:text-cyan-400 hover:underline transition">
                    <span>←</span> Reading Gap Hub
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-brand-600 dark:text-slate-400 dark:hover:text-cyan-400 transition">
                    <span>←</span> Back to Dashboard
                </a>
                <span class="text-slate-400 dark:text-slate-600">/</span>
                <a href="{{ route('reading-gap.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-brand-600 dark:text-slate-400 dark:hover:text-cyan-400 transition">
                    Reading Gap Hub
                </a>
            @endif
            <span class="text-slate-400 dark:text-slate-600">/</span>
            <span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">{{ $account->ca_number }}</span>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>👤</span>
                <span>{{ $account->consumer_name ?: 'Consumer Profile' }}</span>
            </h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono bg-brand-500/10 text-brand-600 dark:text-cyan-400 border border-brand-500/20">
                CA: {{ $account->ca_number }}
            </span>
            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                MRU: {{ $account->mru ? $account->mru->code : 'GENERAL' }}
            </span>
            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                Tariff: {{ $account->tariff_category ?: 'DS1D' }}
            </span>
            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                Basis: {{ $account->billing_basis ?: 'OK' }}
            </span>
        </div>
    </div>

    <!-- Quick Action Controls -->
    <div class="flex items-center gap-2 flex-wrap">
        @if($account->mobile)
            <a href="tel:{{ $account->mobile }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition" title="Call Consumer">
                <span>📞</span> Call
            </a>
            <a href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $account->mobile) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition" title="Chat on WhatsApp">
                <span>💬</span> WhatsApp
            </a>
        @endif
        <button type="button" @click="openInspectionModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-500 text-white shadow-xs transition cursor-pointer">
            <span>🔍</span> Record Visit
        </button>
        <button type="button" @click="triggerQuickPull('{{ $account->ca_number }}')" :disabled="pullingBill" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition cursor-pointer disabled:opacity-50">
            <span x-show="pullingBill" class="w-3 h-3 border-2 border-brand-500 border-t-transparent rounded-full animate-spin"></span>
            <span x-show="!pullingBill">⚡</span> Quick Pull
        </button>
    </div>
</div>
