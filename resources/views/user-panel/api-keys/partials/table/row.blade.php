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

    <!-- Key Prefix / Revealable Secret -->
    <td class="py-4 px-6" x-data="apiKeyRow({{ $key->id }}, '{{ $key->key_prefix }}', {{ $key->isRevealable() ? 'true' : 'false' }})">
        <div class="flex items-center gap-2">
            <!-- Masked state -->
            <div x-show="!revealed" class="flex items-center">
                <code class="font-mono text-xs font-bold text-brand-600 dark:text-cyan-400 bg-brand-50 dark:bg-cyan-950/40 border border-brand-200/50 dark:border-cyan-800/40 px-2 py-0.5 rounded-md select-none">
                    {{ $key->key_prefix }}••••••••
                </code>
            </div>

            <!-- Revealed state -->
            <div x-show="revealed" x-cloak class="flex items-center">
                <code class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/50 dark:border-emerald-800/40 px-2 py-0.5 rounded-md select-all"
                      x-text="plainKey">
                </code>
            </div>

            @if($key->isRevealable())
                <!-- Reveal / Unreveal Toggle Button -->
                <button type="button"
                        @click="toggleReveal()"
                        :disabled="loading"
                        :title="revealed ? 'Hide API Key' : 'Reveal API Key'"
                        class="p-1 rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer disabled:opacity-50">
                    <span x-show="loading" class="animate-spin text-xs">⏳</span>
                    <span x-show="!loading && !revealed" class="text-xs">👁️</span>
                    <span x-show="!loading && revealed" x-cloak class="text-xs">🙈</span>
                </button>

                <!-- Copy Button -->
                <button type="button"
                        @click="copyKey()"
                        :disabled="loading"
                        title="Copy API Key to clipboard"
                        class="p-1 rounded-lg text-slate-500 hover:text-cyan-600 dark:text-slate-400 dark:hover:text-cyan-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer disabled:opacity-50">
                    <span x-show="!copied" class="text-xs">📋</span>
                    <span x-show="copied" x-cloak class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">✓ Copied!</span>
                </button>
            @else
                <span class="text-[10px] text-slate-400 italic" title="Legacy key created before reveal feature. Regenerate to enable reveal.">Legacy</span>
            @endif
        </div>
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
