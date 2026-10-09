{{-- Filter and Search Bar --}}
<div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.payments.manual') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ empty($modeFilter) ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
            All Pending ({{ $pendingPayments->total() }})
        </a>
        <a href="{{ route('admin.payments.manual', ['mode' => 'manual_upi']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $modeFilter === 'manual_upi' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
            📱 UPI Only ({{ $upiPendingCount }})
        </a>
        <a href="{{ route('admin.payments.manual', ['mode' => 'bank_transfer']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $modeFilter === 'bank_transfer' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-slate-900 text-slate-400 hover:text-white' }}">
            🏦 Bank NEFT/IMPS ({{ $bankPendingCount }})
        </a>
    </div>

    <form method="GET" action="{{ route('admin.payments.manual') }}" class="flex items-center gap-2 w-full sm:w-auto">
        <input type="hidden" name="mode" value="{{ $modeFilter }}">
        <div class="relative w-full sm:w-64">
            <span class="absolute left-3 top-2.5 text-slate-500 text-xs">🔍</span>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search UTR, Ref, Agent..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl pl-8 pr-3 py-2 text-slate-200 placeholder-slate-500 focus:ring-amber-500 focus:border-amber-500">
        </div>
        <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition">
            Filter
        </button>
    </form>
</div>
