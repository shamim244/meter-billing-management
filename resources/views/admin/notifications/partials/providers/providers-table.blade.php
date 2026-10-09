{{-- Provider Chain Table --}}
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
                            <div class="flex items-center gap-2 flex-wrap">
                                <span>{{ $p->label }}</span>
                                @if($p->isConfigDecryptionFailed())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30" title="APP_KEY rotated. Click Edit to enter new credentials.">
                                        ⚠️ Re-enter Credentials
                                    </span>
                                @endif
                            </div>
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
                                <button @click="openEditModal({{ json_encode([
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
                                ]) }})" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-indigo-300 rounded-lg text-xs font-semibold transition">
                                    Edit
                                </button>
                                <button @click="openTestModal({{ $p->id }}, '{{ addslashes($p->label) }}')" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-cyan-300 rounded-lg text-xs font-semibold transition">
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
