<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('reports.usage', ['month' => $month ?: now()->month, 'year' => $year ?: now()->year]) }}" class="text-xs font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                        ← ROI Overview
                    </a>
                </div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5 mt-1">
                    <span>⚠️</span> Consecutive Estimated Billing Alerts (LK / MD)
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Consumers billed on estimated basis (Locked Premise <strong class="text-amber-600">LK</strong> or Defective Meter <strong class="text-rose-600">MD</strong>) for 2 or more consecutive cycles.
                </p>
            </div>

            <!-- MRU Filter -->
            <form method="GET" action="{{ route('reports.flagged') }}" class="flex items-center gap-2">
                <select name="mru_id" class="text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500">
                    <option value="">All MRU Workspaces</option>
                    @foreach($mrus as $mru)
                        <option value="{{ $mru->id }}" {{ $mruId === $mru->id ? 'selected' : '' }}>{{ $mru->code }} - {{ $mru->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- Explanatory Notice Banner -->
        <div class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-2xl text-xs text-amber-300 flex items-start gap-3">
            <span class="text-lg">💡</span>
            <div>
                <strong class="font-bold">Operational Intelligence:</strong>
                Consumers flagged here require physical meter reader visits. When an official meter reading is recorded (basis returns to <strong class="underline">OK</strong>), the consecutive estimate counter automatically resets to zero.
            </div>
        </div>

        <!-- Flagged Consumers Table -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-3">CA Number</th>
                            <th class="py-3 px-3">Consumer Name</th>
                            <th class="py-3 px-3">MRU Workspace</th>
                            <th class="py-3 px-3 text-center">Consecutive Cycles</th>
                            <th class="py-3 px-3 text-center">Latest Basis</th>
                            <th class="py-3 px-3 text-center">Cycle Period</th>
                            <th class="py-3 px-3 text-center">Action Required</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                        @forelse($flagged as $item)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                                <td class="py-3 px-3 font-mono font-bold text-blue-600 dark:text-cyan-400">
                                    {{ $item->ca_number }}
                                </td>
                                <td class="py-3 px-3 font-medium text-slate-900 dark:text-white">
                                    {{ $item->consumerAccount?->consumer_name ?? '—' }}
                                </td>
                                <td class="py-3 px-3 text-slate-500 font-mono text-[11px]">
                                    {{ $item->mru ? $item->mru->code : 'GENERAL' }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                        ⚠️ {{ $item->consecutive_count }}x Consecutive
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center font-mono font-bold">
                                    <span class="px-2 py-0.5 rounded text-[11px] {{ $item->billing_basis === 'MD' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                                        {{ $item->billing_basis }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center font-mono text-slate-500 text-[11px]">
                                    {{ $item->billing_month }}/{{ $item->billing_year }}
                                </td>
                                <td class="py-3 px-3 text-center text-[11px] text-slate-400">
                                    Physical Spot Verification Required
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    🎉 No consumers currently have consecutive estimated billing alerts!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
