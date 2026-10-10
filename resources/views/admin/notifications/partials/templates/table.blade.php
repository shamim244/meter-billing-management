<div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
    <div class="p-4 border-b border-slate-800/80 flex items-center justify-between">
        <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider">
            Available Event Templates ({{ $templates->count() }})
        </h2>
        <div class="text-[11px] text-slate-400">
            <span class="text-rose-400 font-bold">CRITICAL</span> = In-App + Email (Cannot be disabled by user) | <span class="text-indigo-400 font-bold">ROUTINE</span> = Standard
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                    <th class="py-3 px-3">Event Type</th>
                    <th class="py-3 px-3 text-center">Channel</th>
                    <th class="py-3 px-3 text-center">Priority</th>
                    <th class="py-3 px-3 text-center">Dispatch Mode</th>
                    <th class="py-3 px-3">Subject / Preview</th>
                    <th class="py-3 px-3 text-center">Status</th>
                    <th class="py-3 px-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                @foreach($templates as $tmpl)
                    <tr class="hover:bg-slate-800/20 transition">
                        <td class="py-3 px-3 font-mono font-bold text-white">
                            {{ $tmpl->event_type }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono bg-slate-950 border border-slate-800 {{ $tmpl->channel === 'email' ? 'text-indigo-300' : 'text-emerald-300' }}">
                                {{ $tmpl->channel }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $tmpl->priority === 'critical' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' }}">
                                {{ $tmpl->priority }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase font-mono {{ $tmpl->dispatch_mode === 'sync' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-slate-800 text-slate-300 border border-slate-700' }}">
                                {{ $tmpl->dispatch_mode ?? 'queued' }}
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            @if($tmpl->subject)
                                <div class="font-semibold text-slate-200">{{ $tmpl->subject }}</div>
                            @endif
                            <div class="text-[11px] text-slate-400 truncate max-w-[320px]">{{ $tmpl->body_template }}</div>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $tmpl->is_active ? 'text-emerald-400' : 'text-slate-500' }}">
                                {{ $tmpl->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-right">
                            <button @click="openEdit({{ $tmpl->toJson() }})" class="px-3 py-1 bg-indigo-600/30 hover:bg-indigo-600 text-indigo-200 rounded-lg text-xs font-semibold transition border border-indigo-500/30">
                                Edit
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
