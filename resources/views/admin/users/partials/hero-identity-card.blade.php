<!-- Hero Identity Card -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
        <div class="flex items-start gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-indigo-600/30 shrink-0">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-2xl font-black text-white tracking-tight">{{ $user->name }}</h1>
                    
                    <!-- Role Pill -->
                    @foreach($user->roles as $role)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $role->name === 'admin' ? 'bg-purple-950 text-purple-300 border border-purple-500/40' : 'bg-slate-800 text-slate-300 border border-slate-700' }}">
                            {{ $role->name }}
                        </span>
                    @endforeach

                    <!-- Status Pill -->
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $user->status === 'active' ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'bg-rose-950 text-rose-300 border border-rose-500/30' }}">
                        {{ $user->status }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 mt-2">
                    <span class="flex items-center gap-1">
                        <span>📧</span> {{ $user->email }}
                    </span>
                    @if($user->email_verified_at)
                        <span class="text-emerald-400 font-bold flex items-center gap-0.5">
                            <span>✓</span> Verified
                        </span>
                    @else
                        <span class="text-amber-400 font-bold">⚠️ Unverified</span>
                    @endif

                    @if($user->phone)
                        <span>•</span>
                        <span class="flex items-center gap-1 font-mono">
                            <span>📞</span> {{ $user->phone }}
                        </span>
                    @endif

                    <span>•</span>
                    <span>Joined {{ $user->created_at->format('M d, Y (h:i A)') }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="bg-slate-900/90 px-4 py-3 rounded-2xl border border-slate-800 text-center min-w-[100px]">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">User ID</div>
                <div class="text-base font-black text-white font-mono mt-0.5">#{{ $user->id }}</div>
            </div>
        </div>
    </div>
</div>
