<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ addModal: false, editModal: false, testModal: false, selectedProviderId: null, selectedProviderLabel: '', driverType: 'smtp', editData: {} }">
        <!-- Header & Action -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>⚡</span> Email Provider Registry & Fallback Chain
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Configure multiple SMTP/API email providers in priority order. When delivery fails, the system automatically falls through to the next enabled provider.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button @click="addModal = true" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-indigo-600/30">
                    <span>+</span> Add Email Provider
                </button>
            </div>
        </div>

        <!-- Sub-Nav Links -->
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.notifications.email_providers.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-md shadow-indigo-600/20">
                Email Providers
            </a>
            <a href="{{ route('admin.notifications.templates.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                Notification Templates
            </a>
            <a href="{{ route('admin.notifications.failed_queue') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                Failed Critical Queue
            </a>
        </div>

        @if(session('error'))
            <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-xs text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-xs text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <!-- Provider Chain Table -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
            <div class="p-4 border-b border-slate-800/80">
                <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider">
                    Configured Email Providers (Fallback Order)
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-3 text-center">Priority</th>
                            <th class="py-3 px-3">Provider Label</th>
                            <th class="py-3 px-3 text-center">Driver Type</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-3">Last Used</th>
                            <th class="py-3 px-3">Last Failure</th>
                            <th class="py-3 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($providers as $p)
                            <tr class="hover:bg-slate-800/20 transition {{ !$p->is_enabled ? 'opacity-50' : '' }}">
                                <td class="py-3 px-3 text-center font-mono font-bold text-indigo-400">
                                    #{{ $p->priority }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-white">
                                    {{ $p->label }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono bg-slate-950 border border-slate-800 text-slate-300">
                                        {{ $p->driver_type }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <form method="POST" action="{{ route('admin.notifications.email_providers.toggle', $p) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border transition {{ $p->is_enabled ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30 hover:bg-rose-500/20 hover:text-rose-300' : 'bg-slate-800 text-slate-400 border-slate-700 hover:bg-emerald-500/20 hover:text-emerald-300' }}" title="Click to toggle status">
                                            {{ $p->is_enabled ? 'Active' : 'Disabled' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="py-3 px-3 text-slate-400 font-mono text-[11px]">
                                    {{ $p->last_used_at ? $p->last_used_at->diffForHumans() : 'Never' }}
                                </td>
                                <td class="py-3 px-3 text-[11px]">
                                    @if($p->last_failure_at)
                                        <span class="text-rose-400 font-mono">{{ $p->last_failure_at->diffForHumans() }}</span>
                                        <div class="text-[10px] text-slate-500 truncate max-w-[200px]" title="{{ $p->last_failure_reason }}">{{ $p->last_failure_reason }}</div>
                                    @else
                                        <span class="text-emerald-400">No failures</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="editData = {{ json_encode([
                                            'id' => $p->id,
                                            'label' => $p->label,
                                            'driver_type' => $p->driver_type,
                                            'priority' => $p->priority,
                                            'is_enabled' => (bool) $p->is_enabled,
                                            'smtp_host' => $p->config['host'] ?? '',
                                            'smtp_port' => $p->config['port'] ?? 587,
                                            'smtp_encryption' => $p->config['encryption'] ?? 'tls',
                                            'smtp_username' => $p->config['username'] ?? '',
                                            'from_address' => $p->config['from_address'] ?? 'notifications@nexgenhub.site',
                                            'from_name' => $p->config['from_name'] ?? 'NBPDCL Billing Platform',
                                            'api_key' => $p->config['api_key'] ?? '',
                                        ]) }}; editModal = true" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-indigo-300 rounded-lg text-xs font-semibold transition">
                                            Edit
                                        </button>
                                        <button @click="selectedProviderId = {{ $p->id }}; selectedProviderLabel = '{{ $p->label }}'; testModal = true" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-cyan-300 rounded-lg text-xs font-semibold transition">
                                            Test Send
                                        </button>
                                        <form method="POST" action="{{ route('admin.notifications.email_providers.destroy', $p) }}" onsubmit="return confirm('Are you sure you want to delete this email provider instance?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 rounded-lg text-xs font-semibold transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-500">
                                    No email providers configured in registry. Click "+ Add Email Provider" above.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Delivery Attempts Log -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
            <div class="p-4 border-b border-slate-800/80">
                <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider">
                    Recent Email Delivery Attempts (Last 25)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-2.5 px-3">Time</th>
                            <th class="py-2.5 px-3">Recipient</th>
                            <th class="py-2.5 px-3">Event</th>
                            <th class="py-2.5 px-3">Provider Used</th>
                            <th class="py-2.5 px-3 text-center">Status</th>
                            <th class="py-2.5 px-3">Failure Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300 font-mono text-[11px]">
                        @forelse($recentDeliveries as $del)
                            <tr>
                                <td class="py-2 px-3 text-slate-400">{{ $del->created_at?->diffForHumans() }}</td>
                                <td class="py-2 px-3 font-sans font-semibold text-white">{{ $del->notification?->user?->email ?? '—' }}</td>
                                <td class="py-2 px-3 text-cyan-400">{{ $del->notification?->event_type }}</td>
                                <td class="py-2 px-3">{{ $del->emailProviderInstance?->label ?? 'None' }}</td>
                                <td class="py-2 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $del->status === 'sent' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' }}">
                                        {{ $del->status }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-slate-400 truncate max-w-[200px]">{{ $del->failed_reason ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-slate-500 font-sans">No recent email deliveries logged.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Provider Modal -->
        <div x-show="addModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="addModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Add Email Provider Instance</h3>
                    <button @click="addModal = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.notifications.email_providers.store') }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Driver Type</label>
                        <select name="driver_type" x-model="driverType" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500">
                            <option value="smtp">SMTP Server (Primary / Default)</option>
                            <option value="resend">Resend API</option>
                            <option value="brevo">Brevo (Sendinblue) API</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Provider Label</label>
                        <input type="text" name="label" required placeholder="e.g. Primary Resend API or Backup SMTP" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Fallback Priority (1 = Highest / Tried First)</label>
                        <input type="number" name="priority" value="1" min="1" max="100" required class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500" />
                    </div>

                    <!-- SMTP Fields -->
                    <template x-if="driverType === 'smtp'">
                        <div class="space-y-3 p-3 bg-slate-950/60 rounded-xl border border-slate-800">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">SMTP Host</label>
                                    <input type="text" name="smtp_host" placeholder="smtp.gmail.com / mail.host.com" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3" />
                                </div>
                                <div>
                                    <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">SMTP Port</label>
                                    <input type="number" name="smtp_port" value="587" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Username</label>
                                    <input type="text" name="smtp_username" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3" />
                                </div>
                                <div>
                                    <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Password</label>
                                    <input type="password" name="smtp_password" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Encryption</label>
                                <select name="smtp_encryption" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3">
                                    <option value="tls">TLS (Standard - Port 587)</option>
                                    <option value="ssl">SSL (Port 465)</option>
                                    <option value="null">None</option>
                                </select>
                            </div>
                        </div>
                    </template>

                    <!-- API Key Fields -->
                    <template x-if="driverType === 'resend' || driverType === 'brevo'">
                        <div class="space-y-3 p-3 bg-slate-950/60 rounded-xl border border-slate-800">
                            <div>
                                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1" x-text="driverType === 'resend' ? 'Resend API Key (re_...)' : 'Brevo API Key (xkeysib-...)'"></label>
                                <input type="password" name="api_key" placeholder="Enter secret API key" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3" />
                            </div>
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">From Address</label>
                            <input type="email" name="from_address" value="notifications@nexgenhub.site" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3" />
                        </div>
                        <div>
                            <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">From Name</label>
                            <input type="text" name="from_name" value="NBPDCL Billing Platform" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="is_enabled" value="1" checked id="add_enabled" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500" />
                        <label for="add_enabled" class="text-xs text-slate-300 font-semibold">Enable immediately in fallback chain</label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                        <button type="button" @click="addModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-lg shadow-indigo-600/30">Save Provider</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Provider Modal -->
        <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="editModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Edit Email Provider Instance</h3>
                    <button @click="editModal = false" class="text-slate-400 hover:text-white">&times;</button>
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

                    <!-- SMTP Fields -->
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

                    <!-- API Key Fields -->
                    <template x-if="editData.driver_type === 'resend' || editData.driver_type === 'brevo'">
                        <div class="space-y-3 p-3 bg-slate-950/60 rounded-xl border border-slate-800">
                            <div>
                                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1" x-text="editData.driver_type === 'resend' ? 'Resend API Key' : 'Brevo API Key'"></label>
                                <input type="password" name="api_key" placeholder="Leave blank to keep existing key" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white py-2 px-3" />
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
                        <button type="button" @click="editModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-lg shadow-indigo-600/30">Update Provider</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Test Send Modal -->
        <div x-show="testModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="testModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Direct Test Send</h3>
                    <button @click="testModal = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>

                <p class="text-xs text-slate-400">
                    Send a test email directly via <strong class="text-white" x-text="selectedProviderLabel"></strong> (bypasses fallback chain).
                </p>

                <form :action="'/admin/notifications/email-providers/' + selectedProviderId + '/test-send'" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Recipient Email</label>
                        <input type="email" name="test_recipient" required placeholder="admin@example.com" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2.5 px-3 focus:ring-indigo-500" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                        <button type="button" @click="testModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl font-bold">Send Test</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
