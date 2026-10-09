<!-- MRU Workspaces List -->
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h2 class="text-base font-bold text-white flex items-center gap-2">
            <span>📁</span> MRU Workspaces ({{ $mrus->count() }})
        </h2>
        <span class="text-xs text-slate-400 font-medium">Permanent consumer master lists</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/80 text-slate-400 uppercase font-bold text-[10px]">
                <tr>
                    <th class="py-3 px-4">MRU Code</th>
                    <th class="py-3 px-4">Name</th>
                    <th class="py-3 px-4 text-center">Consumers</th>
                    <th class="py-3 px-4 text-center">Cycles</th>
                    <th class="py-3 px-4 text-center">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-medium">
                @forelse($mrus as $mru)
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="py-3 px-4 font-mono font-bold text-cyan-400">
                            {{ $mru->code }}
                        </td>
                        <td class="py-3 px-4 text-white font-semibold">
                            {{ $mru->name }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-bold text-slate-200">
                            {{ number_format($mru->consumer_accounts_count) }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-indigo-400">
                            {{ $mru->billing_cycles_count }}
                        </td>
                        <td class="py-3 px-4 text-center text-slate-500">
                            {{ $mru->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-500">
                            No MRU workspaces created by this user yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
