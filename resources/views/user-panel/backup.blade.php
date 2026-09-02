@extends('layouts.user-panel')

@section('content')
<div class="space-y-6 max-w-5xl">

    <!-- Header & Breadcrumb -->
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
            <a href="{{ route('user-panel.index') }}" class="hover:text-slate-600 dark:hover:text-white transition">User Panel</a>
            <span>/</span>
            <span class="text-brand-600 dark:text-cyan-400">Data Portability</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-3">
            <span>💾 My Workspace Data Export & Backup</span>
            <span class="text-xs font-mono font-normal px-2.5 py-1 rounded-full bg-cyan-950 text-cyan-300 border border-cyan-800">
                1-Click Portability
            </span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Download a full, standalone archive of your MRU books, consumer registers, 4-box reading ledgers, and official bill PDFs.
        </p>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-500/40 text-xs font-bold text-emerald-300 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200">✕</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-xs font-bold text-rose-300 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-400 hover:text-rose-200">✕</button>
        </div>
    @endif

    <!-- Workspace Summary Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400">My MRU Books</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ $stats['total_mrus'] }}</div>
            <span class="text-[10px] text-slate-500">Active feeder workspaces</span>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400">Total Consumers</span>
            <div class="text-2xl font-black text-cyan-600 dark:text-cyan-400 font-mono mt-1">{{ $stats['total_consumers'] }}</div>
            <span class="text-[10px] text-slate-500">Master account registry</span>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400">Monthly Bills</span>
            <div class="text-2xl font-black text-brand-600 dark:text-brand-400 font-mono mt-1">{{ $stats['total_bills'] }}</div>
            <span class="text-[10px] text-slate-500">Historical cycle records</span>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400">Wallet Balance</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1">₹{{ number_format($stats['wallet_balance'], 2) }}</div>
            <span class="text-[10px] text-slate-500">Available credit balance</span>
        </div>

    </div>

    <!-- Export Action Banner -->
    <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-tr from-brand-950/40 via-slate-900 to-cyan-950/40 border border-cyan-500/30 shadow-xl space-y-6">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                    🛡️ Zero Lock-in Guarantee
                </span>
                <h2 class="text-xl font-black text-white">Download Complete Workspace Package</h2>
                <p class="text-xs text-slate-300 max-w-xl leading-relaxed">
                    Generate a clean, structured ZIP archive containing all your agency data formatted for Excel, DISCOM audits, and offline archiving.
                </p>
            </div>

            <form method="POST" action="{{ route('user-panel.backup.download') }}">
                @csrf
                <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center gap-3 px-6 py-3.5 rounded-2xl bg-gradient-to-r from-cyan-400 via-brand-500 to-brand-600 hover:from-cyan-300 hover:to-brand-400 text-slate-950 font-black text-xs shadow-lg shadow-cyan-500/25 transition transform hover:-translate-y-0.5">
                    <span class="text-base">📦</span>
                    <span>Download Workspace ZIP Now</span>
                </button>
            </form>
        </div>

        <!-- Manifest Preview Grid -->
        <div class="pt-6 border-t border-white/10 grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2">
                <div class="flex items-center gap-2 text-xs font-bold text-white">
                    <span class="text-cyan-400">📄</span>
                    <span>Structured CSV Master Ledgers:</span>
                </div>
                <ul class="text-[11px] text-slate-400 space-y-1 pl-6 list-disc">
                    <li><code class="text-slate-300 font-mono">ledger/01_mrus_master.csv</code> — All feeder books & lock states</li>
                    <li><code class="text-slate-300 font-mono">ledger/02_consumers_registry.csv</code> — Names, meters, addresses & tariffs</li>
                    <li><code class="text-slate-300 font-mono">ledger/03_monthly_reading_ledger.csv</code> — 4-Box readings & audit tags</li>
                    <li><code class="text-slate-300 font-mono">ledger/04_wallet_statement.csv</code> — Financial statement & charges</li>
                </ul>
            </div>

            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2">
                <div class="flex items-center gap-2 text-xs font-bold text-white">
                    <span class="text-emerald-400">📑</span>
                    <span>Bundled PDF Archives:</span>
                </div>
                <ul class="text-[11px] text-slate-400 space-y-1 pl-6 list-disc">
                    <li><code class="text-slate-300 font-mono">bills/{MRU}/{Month}/{CA}.pdf</code> — Organized hierarchical bill files</li>
                    <li><code class="text-slate-300 font-mono">manifest.json</code> — Cryptographic metadata & timestamp proof</li>
                    <li>Compatible with Microsoft Excel, LibreOffice, and DISCOM portals</li>
                </ul>
            </div>

        </div>

    </div>

</div>
@endsection
