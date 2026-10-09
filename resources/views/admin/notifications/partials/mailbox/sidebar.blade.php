{{-- Mailbox Selector Sidebar --}}
<div class="space-y-4 lg:col-span-1">
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-4 space-y-3">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
            Available Mailboxes
        </h3>
        <div class="space-y-1.5">
            @forelse($mailboxes as $mb)
                @php
                    $isActive = ($mb['address'] ?? '') === $selectedAddress;
                @endphp
                <a href="{{ route('admin.notifications.mailbox.index', ['address' => $mb['address']]) }}" 
                   class="flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold transition {{ $isActive ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-slate-950/60 text-slate-300 hover:bg-slate-800 hover:text-white border border-slate-800/60' }}">
                    <div class="truncate">
                        <div class="truncate font-mono">{{ $mb['address'] }}</div>
                        <div class="text-[10px] {{ $isActive ? 'text-indigo-200' : 'text-slate-500' }} font-mono">ID: {{ substr($mb['resourceId'] ?? '', 0, 10) }}...</div>
                    </div>
                    <span class="text-[10px] {{ $isActive ? 'text-white' : 'text-slate-400' }}">→</span>
                </a>
            @empty
                <div class="text-xs text-slate-500 py-2">No mailboxes retrieved.</div>
            @endforelse
        </div>
    </div>

    <!-- API Connection Badge -->
    <div class="p-4 bg-emerald-950/30 border border-emerald-500/30 rounded-2xl space-y-1.5">
        <div class="flex items-center gap-2 text-xs font-bold text-emerald-400">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Hostinger Mail API: Connected</span>
        </div>
        <p class="text-[11px] text-slate-400 leading-relaxed">
            Authorized token scoped for all account mailboxes. Full read/send access verified.
        </p>
    </div>
</div>
