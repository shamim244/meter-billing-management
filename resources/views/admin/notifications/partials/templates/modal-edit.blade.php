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
