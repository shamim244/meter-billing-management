{{-- Physical Inspection Visits Chronological Audit Ledger --}}
<div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
        <div class="flex items-center gap-2">
            <span class="text-xl">🔍</span>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Physical Visit Audit Trail ({{ $inspections->count() }})</h3>
                <p class="text-[11px] text-slate-500">Chronological history of verified on-site wall meter inspections</p>
            </div>
        </div>
        @if($inspections->isNotEmpty())
            <button type="button" @click="clearAllInspections()" class="text-xs font-bold text-rose-500 hover:text-rose-400 transition cursor-pointer flex items-center gap-1">
                <span>🗑️</span> Clear All Visits
            </button>
        @endif
    </div>

    @if($inspections->isEmpty())
        <div class="py-12 text-center space-y-3">
            <div class="text-3xl">📋</div>
            <div class="text-xs font-bold text-slate-700 dark:text-slate-300">No On-Site Inspections Recorded</div>
            <p class="text-[11px] text-slate-500 max-w-sm mx-auto">Physical meter verification has not been performed for this consumer yet. Click below to record the first wall meter visit.</p>
            <button type="button" @click="openInspectionModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">
                <span>+</span> Record First Visit
            </button>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-bold">
                        <th class="py-2.5 px-3">Visit Date</th>
                        <th class="py-2.5 px-3 text-right">Physical Reading</th>
                        <th class="py-2.5 px-3">Inspector</th>
                        <th class="py-2.5 px-3">Field Notes & Remarks</th>
                        <th class="py-2.5 px-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @foreach($inspections as $item)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3 px-3 font-mono">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $item->inspected_at ? $item->inspected_at->format('d M Y') : 'N/A' }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $item->inspected_at ? $item->inspected_at->diffForHumans() : '' }}</span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-cyan-500 text-sm">
                                <template x-if="editingInspectionId === {{ $item->id }}">
                                    <input type="number" x-model="editForm.physical_reading" class="w-24 bg-slate-950 border border-slate-700 rounded-lg px-2 py-1 text-xs text-right text-white">
                                </template>
                                <template x-if="editingInspectionId !== {{ $item->id }}">
                                    <span>{{ number_format((int)$item->physical_reading) }} kWh</span>
                                </template>
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-medium text-slate-900 dark:text-slate-200">{{ $item->user?->name ?? 'Operator' }}</span>
                            </td>
                            <td class="py-3 px-3">
                                <template x-if="editingInspectionId === {{ $item->id }}">
                                    <input type="text" x-model="editForm.notes" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2 py-1 text-xs text-white">
                                </template>
                                <template x-if="editingInspectionId !== {{ $item->id }}">
                                    <span class="text-slate-600 dark:text-slate-400">{{ $item->notes ?: 'Routine physical verification' }}</span>
                                </template>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <template x-if="editingInspectionId === {{ $item->id }}">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" @click="saveEditInspection({{ $item->id }})" class="px-2 py-1 rounded-lg bg-emerald-600 text-white font-bold text-[10px]">Save</button>
                                        <button type="button" @click="cancelEditInspection()" class="px-2 py-1 rounded-lg bg-slate-700 text-white text-[10px]">Cancel</button>
                                    </div>
                                </template>
                                <template x-if="editingInspectionId !== {{ $item->id }}">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" @click="startEditInspection({ id: {{ $item->id }}, physical_reading: '{{ $item->physical_reading }}', inspected_at: '{{ $item->inspected_at?->format('Y-m-d') }}', notes: '{{ addslashes($item->notes ?? '') }}' })" class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition" title="Edit Visit">✏️</button>
                                        <button type="button" @click="deleteSingleInspection({{ $item->id }})" class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 transition" title="Delete Visit">🗑️</button>
                                    </div>
                                </template>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
