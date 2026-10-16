<!-- Recent Inspections Audit Log Feed -->
<div class="bg-slate-950 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
    <div class="p-4 border-b border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-base">📋</span>
            <h3 class="text-sm font-bold text-white">Live Field Inspections & Meter Replacement Feed</h3>
        </div>
        <span class="text-xs text-slate-400">Most recent 25 events</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="border-b border-slate-800 bg-slate-900/60 text-slate-400 font-bold">
                    <th class="py-3 px-4">Date & Time</th>
                    <th class="py-3 px-3">CA & Consumer</th>
                    <th class="py-3 px-3">Agent</th>
                    <th class="py-3 px-3 text-right">Physical Reading</th>
                    <th class="py-3 px-4">Inspection / Replacement Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <template x-if="loading">
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-500">
                            Loading audit log...
                        </td>
                    </tr>
                </template>
                <template x-if="!loading && inspections.length === 0">
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-500">
                            No meter inspections recorded yet.
                        </td>
                    </tr>
                </template>
                <template x-for="i in inspections" :key="i.id">
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="py-3 px-4 font-mono text-[11px] text-slate-400" x-text="i.inspected_at"></td>
                        <td class="py-3 px-3">
                            <span class="font-bold text-white block" x-text="i.consumer_name"></span>
                            <span class="font-mono text-slate-400 text-[11px]" x-text="i.ca_number"></span>
                        </td>
                        <td class="py-3 px-3 text-slate-300" x-text="i.agent_name"></td>
                        <td class="py-3 px-3 text-right font-mono font-bold text-cyan-400" x-text="i.physical_reading + ' kWh'"></td>
                        <td class="py-3 px-4 text-slate-400 text-[11px]">
                            <span x-text="i.notes"></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
