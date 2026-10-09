<!-- Top Back & Action Bar -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition">
        <span>←</span> Back to All Users
    </a>

    <div class="flex flex-wrap items-center gap-2">
        <!-- Impersonate Button -->
        @if($user->id !== auth()->id() && !$user->hasRole('admin'))
            <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
                @csrf
                <button type="submit" onclick="return confirm('Log in as {{ $user->name }}? You will be redirected to their dashboard.');" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white rounded-xl text-xs font-black shadow-lg shadow-amber-600/20 transition active:scale-95">
                    <span>🎭</span> Login as User
                </button>
            </form>
        @endif

        <!-- Direct Notification Dispatcher -->
        <button type="button" @click="showNotificationModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 rounded-xl text-xs font-bold transition">
            <span>📢</span> Send Alert
        </button>

        <!-- Grant Plan / Extend Button -->
        <button type="button" @click="showGrantModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/20 transition">
            <span>🎁</span> Grant / Extend Plan
        </button>

        <!-- Override Quotas Button -->
        <button type="button" @click="showQuotaModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-amber-300 border border-amber-500/30 rounded-xl text-xs font-bold transition">
            <span>🎯</span> Quotas
        </button>

        <!-- Manage Wallet Link -->
        <a href="{{ route('admin.wallets.show', $user->id) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-indigo-300 border border-indigo-500/30 rounded-xl text-xs font-bold transition">
            <span>👛</span> Wallet
        </a>

        <!-- Edit Profile -->
        <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 rounded-xl text-xs font-bold transition">
            <span>✏️</span> Edit
        </a>

        <!-- Suspend / Activate Toggle -->
        @if($user->id !== auth()->id())
            <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $user->status === 'active' ? 'bg-rose-950/60 hover:bg-rose-900/80 text-rose-300 border border-rose-500/30' : 'bg-emerald-950/60 hover:bg-emerald-900/80 text-emerald-300 border border-emerald-500/30' }}">
                    {{ $user->status === 'active' ? '🚫 Suspend' : '✓ Activate' }}
                </button>
            </form>

            <!-- Granular Clean Data & Storage Console Button -->
            <button type="button" @click="showCleanupModal = true; cleanupTab = 'pdfs'" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-600/40 transition flex items-center gap-1.5 shadow-sm">
                <span>🧹</span> Clean Data / Storage
            </button>
        @endif
    </div>
</div>
