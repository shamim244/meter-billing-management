<x-admin-layout>
    <x-slot name="header">
        MRU Directory & Area Management
    </x-slot>

    <div class="space-y-6">
        <!-- Top Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <form method="GET" action="{{ route('admin.mrus.index') }}" class="flex items-center gap-2 max-w-md w-full">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search MRU code, village or area name..." class="w-full text-sm bg-slate-950 border-slate-800 rounded-xl px-4 py-2 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold transition">
                    Search
                </button>
            </form>
        </div>

        <!-- MRU Table -->
        <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-900 border-b border-slate-800 text-xs uppercase font-bold text-slate-500 tracking-wider">
                        <tr>
                            <th class="py-4 px-6">MRU Code</th>
                            <th class="py-4 px-6">Village / Area Name</th>
                            <th class="py-4 px-6 text-center">Consumers</th>
                            <th class="py-4 px-6 text-center">Bills Stored</th>
                            <th class="py-4 px-6 text-center">Status</th>
                            <th class="py-4 px-6 text-center">Quick Update</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 font-medium">
                        @forelse($mrus as $mru)
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-4 px-6 font-mono font-bold text-cyan-300">
                                    {{ $mru->code }}
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-white font-semibold">{{ $mru->name }}</span>
                                    <span class="block text-xs text-slate-500 font-mono mt-0.5">{{ $mru->full_identifier }}</span>
                                </td>
                                <td class="py-4 px-6 text-center font-mono font-bold text-white">
                                    {{ number_format($mru->consumer_accounts_count) }}
                                </td>
                                <td class="py-4 px-6 text-center font-mono font-bold text-indigo-400">
                                    {{ number_format($mru->bill_records_count) }}
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase {{ $mru->status === 'active' ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'bg-rose-950 text-rose-300 border border-rose-500/30' }}">
                                        {{ $mru->status }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <form method="POST" action="{{ route('admin.mrus.update', $mru) }}" class="flex items-center justify-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="name" value="{{ $mru->name }}" class="text-xs bg-slate-900 border-slate-800 rounded-lg px-2.5 py-1 text-white w-36 focus:ring-indigo-500">
                                        <select name="status" class="text-xs bg-slate-900 border-slate-800 rounded-lg px-2 py-1 text-white focus:ring-indigo-500">
                                            <option value="active" {{ $mru->status === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ $mru->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        <button type="submit" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold transition">
                                            Save
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    No MRUs registered yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($mrus->hasPages())
                <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/60">
                    {{ $mrus->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
