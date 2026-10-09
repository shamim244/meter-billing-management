<!-- Personalized API Key Notification Banner -->
@auth
    <div class="border-b border-cyan-900/50 bg-gradient-to-r from-cyan-950/60 via-slate-900/90 to-brand-950/60 py-2.5 px-4 sm:px-6 lg:px-8 text-xs text-slate-300">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="text-base">👋</span>
                <span>Logged in as <strong>{{ $user->name }}</strong>. 
                    @if($activeKey)
                        Code samples are pre-filled with your active key: <code class="font-mono text-cyan-300 font-bold px-1.5 py-0.5 bg-black/40 rounded">{{ $activeKey }}</code>
                    @else
                        You don't have an active API key yet. <a href="{{ route('user-panel.api-keys') }}" class="text-cyan-400 font-bold underline hover:text-cyan-300">Generate one now in 5 seconds</a>.
                    @endif
                </span>
            </div>
            <a href="#try-it-out" class="text-[11px] font-bold text-cyan-400 hover:underline shrink-0">
                Jump to Live Console ↓
            </a>
        </div>
    </div>
@endauth
