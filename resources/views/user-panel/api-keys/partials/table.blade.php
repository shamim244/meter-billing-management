<!-- Active API Keys Table -->
<div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xl overflow-hidden">
    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <div>
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Active API Keys</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Keys permitted to query, update readings, and batch-sync data on your behalf.</p>
        </div>
        <span class="text-xs font-mono font-bold text-slate-400">
            {{ $apiKeys->count() }} {{ Str::plural('Key', $apiKeys->count()) }}
        </span>
    </div>

    @if($apiKeys->isEmpty())
        <div class="p-12 text-center space-y-4">
            <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800/80 text-slate-400 dark:text-slate-500 flex items-center justify-center text-2xl mx-auto">
                🔑
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">No API Keys Found</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1">
                    Generate your first API key to connect your Phone ADB automation script, Flutter field app, or custom client.
                </p>
            </div>
            <button @click="createModalOpen = true" 
                    type="button" 
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition shadow-md cursor-pointer">
                <span>＋ Generate First Key</span>
            </button>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-100 dark:border-slate-800 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                    <tr>
                        <th class="py-3.5 px-6">Name / Purpose</th>
                        <th class="py-3.5 px-6">Key Token</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Expires</th>
                        <th class="py-3.5 px-6">Last Used</th>
                        <th class="py-3.5 px-6">Created</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @foreach($apiKeys as $key)
                        @php
                            $isExpired = $key->expires_at && $key->expires_at->isPast();
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                            <!-- Name -->
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>💻</span>
                                    <span>{{ $key->name }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    {{ in_array('*', $key->abilities ?? ['*'], true) ? 'Full Access (*)' : implode(', ', $key->abilities ?? []) }}
                                </div>
                            </td>

                            <!-- Key Prefix Masked -->
                            <td class="py-4 px-6">
                                <code class="font-mono text-xs font-bold text-brand-600 dark:text-cyan-400 bg-brand-50 dark:bg-cyan-950/40 border border-brand-200/50 dark:border-cyan-800/40 px-2 py-0.5 rounded-md">
                                    {{ $key->key_prefix }}••••••••
                                </code>
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-6">
                                @if($isExpired)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @endif
                            </td>

                            <!-- Expires -->
                            <td class="py-4 px-6">
                                @if($key->expires_at)
                                    <div class="font-medium {{ $isExpired ? 'text-rose-500 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                        {{ $key->expires_at->diffForHumans() }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                        {{ $key->expires_at->format('M d, Y') }}
                                    </div>
                                @else
                                    <span class="text-slate-500 dark:text-slate-400 font-semibold">Never Expires</span>
                                @endif
                            </td>

                            <!-- Last Used -->
                            <td class="py-4 px-6">
                                @if($key->last_used_at)
                                    <div class="font-medium text-slate-700 dark:text-slate-300">
                                        {{ $key->last_used_at->diffForHumans() }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                        {{ $key->last_ip ? 'IP: '.$key->last_ip : 'Recorded' }}
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">Never</span>
                                @endif
                            </td>

                            <!-- Created -->
                            <td class="py-4 px-6 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                {{ $key->created_at ? $key->created_at->format('M d, Y') : 'N/A' }}
                            </td>

                            <!-- Revoke Action -->
                            <td class="py-4 px-6 text-right">
                                <button @click="openRevoke({{ $key->id }}, '{{ addslashes($key->name) }}')"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 font-bold text-xs transition border border-rose-200/60 dark:border-rose-800/40 cursor-pointer">
                                    <span>🗑️ Revoke</span>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
