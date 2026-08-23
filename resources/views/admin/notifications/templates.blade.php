<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
        editModal: false,
        previewModal: false,
        activeTemplate: null,
        previewSubject: '',
        previewBody: '',
        openEdit(t) {
            this.activeTemplate = Object.assign({}, t);
            this.editModal = true;
        },
        async openPreview() {
            if (!this.activeTemplate) return;
            const res = await fetch('{{ route('admin.notifications.templates.preview') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    subject: this.activeTemplate.subject,
                    body_template: this.activeTemplate.body_template
                })
            });
            const data = await res.json();
            this.previewSubject = data.subject || '(No Subject - In-App)';
            this.previewBody = data.formatted_html;
            this.previewModal = true;
        }
    }">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>📑</span> Notification Templates & Priority Mapping
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Manage message copies, priority routing rules, and merge placeholders for all system events.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('admin.notifications.templates.reset') }}" onsubmit="return confirm('Reset all notification templates to factory defaults?');">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-2">
                        <span>🔄</span> Reset to Defaults
                    </button>
                </form>
            </div>
        </div>

        <!-- Sub-Nav Links -->
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.notifications.email_providers.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                Email Providers
            </a>
            <a href="{{ route('admin.notifications.templates.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-md shadow-indigo-600/20">
                Notification Templates
            </a>
            <a href="{{ route('admin.notifications.failed_queue') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                Failed Critical Queue
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-xs text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <!-- Templates List -->
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

        <!-- Edit Modal -->
        <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="editModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-xl w-full shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>Edit Template:</span>
                        <span class="text-indigo-400 font-mono text-xs" x-text="activeTemplate?.event_type"></span>
                    </h3>
                    <button @click="editModal = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>

                <template x-if="activeTemplate">
                    <form :action="'/admin/notifications/templates/' + activeTemplate.id" method="POST" class="space-y-4 text-xs">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Priority Level</label>
                                <select name="priority" x-model="activeTemplate.priority" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
                                    <option value="routine">Routine (Standard)</option>
                                    <option value="critical">CRITICAL (Forced In-App)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Dispatch Mode</label>
                                <select name="dispatch_mode" x-model="activeTemplate.dispatch_mode" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
                                    <option value="sync">Sync (Immediate with 8s timeout)</option>
                                    <option value="queued">Queued (Background worker)</option>
                                </select>
                            </div>
                        </div>

                        <div x-show="activeTemplate.channel === 'email'">
                            <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Email Subject Line</label>
                            <input type="text" name="subject" x-model="activeTemplate.subject" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-slate-400 font-bold uppercase text-[10px]">Message Body Template</label>
                                <button type="button" @click="openPreview()" class="text-xs text-cyan-400 hover:underline font-semibold">
                                    👁️ Preview with Sample Data
                                </button>
                            </div>
                            <textarea name="body_template" rows="5" x-model="activeTemplate.body_template" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 font-mono text-xs focus:ring-indigo-500"></textarea>
                            <p class="text-[10px] text-slate-500 mt-1">
                                Supported placeholders: <code class="text-slate-400">{agent_name}</code>, <code class="text-slate-400">{amount}</code>, <code class="text-slate-400">{balance}</code>, <code class="text-slate-400">{plan_name}</code>, <code class="text-slate-400">{mru_code}</code>, <code class="text-slate-400">{days_remaining}</code>, <code class="text-slate-400">{grace_period_ends_at}</code>, <code class="text-slate-400">{admin_name}</code>
                            </p>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" name="is_active" value="1" x-model="activeTemplate.is_active" id="edit_active" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500" />
                            <label for="edit_active" class="text-xs text-slate-300 font-semibold">Template Active</label>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                            <button type="button" @click="editModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl font-bold">Cancel</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-lg shadow-indigo-600/30">Save Template</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- Live Preview Modal -->
        <div x-show="previewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="previewModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Live Render Preview</h3>
                    <button @click="previewModal = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 font-bold uppercase text-[10px]">Subject:</span>
                        <div class="text-white font-semibold mt-0.5" x-text="previewSubject"></div>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase text-[10px]">Rendered Body:</span>
                        <div class="mt-1 p-4 bg-slate-950 rounded-xl border border-slate-800 text-slate-200 leading-relaxed" x-html="previewBody"></div>
                    </div>
                </div>

                <div class="flex justify-end pt-3 border-t border-slate-800">
                    <button type="button" @click="previewModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl font-bold text-xs">Close</button>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
