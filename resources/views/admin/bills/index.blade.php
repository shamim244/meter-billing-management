<x-admin-layout>
    <x-slot name="header">
        Global Bills Inspector (All Tenants)
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Toolbar -->
        <form method="GET" action="{{ route('admin.bills.index') }}" class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Tenant Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tenant User</label>
                    <select name="user_id" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
                        <option value="">All Tenants</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- MRU Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">MRU / Area</label>
                    <select name="mru_id" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
                        <option value="">All MRUs</option>
                        @foreach($mrus as $m)
                            <option value="{{ $m->id }}" {{ $mruId == $m->id ? 'selected' : '' }}>{{ $m->code }} - {{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Billing Month -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Month</label>
                    <select name="month" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
                        <option value="">All Months</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Billing Year -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Year</label>
                    <select name="year" class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-indigo-500">
                        <option value="">All Years</option>
                        @php
                            $currYear = (int) date('Y');
                            $billYears = range(max(2020, $currYear - 4), $currYear + 3);
                            rsort($billYears);
                        @endphp
                        @foreach($billYears as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Input -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Search CA / Name</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search..." class="w-full bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:ring-indigo-500">
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 pt-2 border-t border-slate-900">
                <a href="{{ route('admin.bills.index') }}" class="w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-900 transition text-center">Reset</a>
                <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow transition text-center">Apply Filters</button>
            </div>
        </form>

        <!-- Global Bills Table -->
        <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-900 border-b border-slate-800 text-xs uppercase font-bold text-slate-500 tracking-wider">
                        <tr>
                            <th class="py-4 px-6">CA Number</th>
                            <th class="py-4 px-6">Consumer Name</th>
                            <th class="py-4 px-6">Tenant Owner</th>
                            <th class="py-4 px-6">MRU / Area</th>
                            <th class="py-4 px-6 text-right">Amount</th>
                            <th class="py-4 px-6 text-center">Period</th>
                            <th class="py-4 px-6 text-center">Units</th>
                            <th class="py-4 px-6 text-center">PDF</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 font-medium">
                        @forelse($bills as $bill)
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-4 px-6 font-mono font-bold text-indigo-400">
                                    {{ $bill->ca_number }}
                                </td>
                                <td class="py-4 px-6 text-white font-semibold truncate max-w-[200px]">
                                    {{ $bill->consumer_name ?: '—' }}
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-400">
                                    {{ $bill->user ? $bill->user->name : 'Unknown' }}
                                    <span class="block text-[11px] text-slate-600">{{ $bill->user ? $bill->user->email : '' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 rounded text-xs font-mono font-semibold bg-slate-900 text-cyan-300 border border-slate-800">
                                        {{ $bill->mru ? $bill->mru->code : 'UNKNOWN' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right font-extrabold text-white">
                                    ₹{{ number_format($bill->total_amount, 2) }}
                                </td>
                                <td class="py-4 px-6 text-center font-mono text-xs text-slate-400">
                                    {{ $bill->billing_month }}/{{ $bill->billing_year }}
                                </td>
                                <td class="py-4 px-6 text-center font-mono text-xs">
                                    {{ $bill->units_consumed ?? '—' }}
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @if($bill->pdf_path)
                                        <a href="{{ route('bills.pdf', $bill) }}" target="_blank" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-950 hover:bg-indigo-900 text-indigo-300 border border-indigo-500/20 transition">
                                            PDF
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-600 italic">No File</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-500">
                                    No bills found matching search and filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($bills->hasPages())
                <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/60">
                    {{ $bills->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
