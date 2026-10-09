{{-- Edit Provider Modal --}}
<div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div @click.away="closeEditModal()" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white">Edit Email Provider Instance</h3>
            <button @click="closeEditModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form :action="'/admin/notifications/email-providers/' + editData.id" method="POST" class="space-y-4 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Driver Type</label>
                <input type="text" disabled :value="editData.driver_type ? editData.driver_type.toUpperCase() : ''" class="w-full bg-slate-950/50 border-slate-800 rounded-xl text-slate-400 py-2 px-3 cursor-not-allowed uppercase font-mono" />
            </div>

            <div>
                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Provider Label</label>
                <input type="text" name="label" x-model="editData.label" required class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500" />
            </div>

            <div>
                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Fallback Priority (1 = Highest / Tried First)</label>
                <input type="number" name="priority" x-model="editData.priority" min="1" max="100" required class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500" />
            </div>

            {{-- SMTP Fields --}}
            <template x-if="editData.driver_type === 'smtp'">
                <div class="space-y-3 p-3 bg-slate-950/60 rounded-xl border border-slate-800">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">SMTP Host</label>
                            <input type="text" name="smtp_host" x-model="editData.smtp_host" placeholder="smtp.gmail.com / mail.host.com" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3" />
                        </div>
                        <div>
                            <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">SMTP Port</label>
                            <input type="number" name="smtp_port" x-model="editData.smtp_port" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Username</label>
                            <input type="text" name="smtp_username" x-model="editData.smtp_username" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3" />
                        </div>
                        <div>
                            <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Password (Leave blank to keep unchanged)</label>
                            <input type="password" name="smtp_password" placeholder="••••••••" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Encryption</label>
                        <select name="smtp_encryption" x-model="editData.smtp_encryption" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3">
                            <option value="tls">TLS (Standard - Port 587)</option>
                            <option value="ssl">SSL (Port 465)</option>
                            <option value="null">None</option>
                        </select>
                    </div>
                </div>
            </template>

            {{-- API Key Fields --}}
            <template x-if="editData.driver_type === 'resend' || editData.driver_type === 'brevo' || editData.driver_type === 'hostinger'">
                <div class="space-y-3 p-3 bg-slate-950/60 rounded-xl border border-slate-800">
                    <div>
                        <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1" x-text="editData.driver_type === 'hostinger' ? 'Hostinger Mail API Token' : (editData.driver_type === 'resend' ? 'Resend API Key' : 'Brevo API Key')"></label>
                        <input type="password" name="api_key" placeholder="Leave blank to keep existing token" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3 font-mono" />
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">From Address</label>
                    <input type="email" name="from_address" x-model="editData.from_address" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3" />
                </div>
                <div>
                    <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">From Name</label>
                    <input type="text" name="from_name" x-model="editData.from_name" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3" />
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_enabled" value="1" :checked="editData.is_enabled" id="edit_enabled" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500" />
                <label for="edit_enabled" class="text-xs text-slate-300 font-semibold">Enabled in fallback chain</label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="button" @click="closeEditModal()" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl font-bold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-lg shadow-indigo-600/30">Update Provider</button>
            </div>
        </form>
    </div>
</div>
