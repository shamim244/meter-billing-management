@if(session()->has('impersonated_by'))
    <div class="bg-gradient-to-r from-amber-600 via-orange-600 to-amber-700 text-white px-4 py-2.5 shadow-lg border-b border-amber-500/50 sticky top-0 z-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 animate-in slide-in-from-top-2 duration-200">
        <div class="flex items-center gap-3">
            <span class="p-1.5 bg-black/20 rounded-lg text-lg animate-bounce">🎭</span>
            <div class="text-xs">
                <span class="font-extrabold uppercase tracking-wider bg-black/25 px-2 py-0.5 rounded text-[10px] mr-1.5 border border-white/20">
                    Impersonation Active
                </span>
                <span>You are currently viewing as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}).</span>
            </div>
        </div>

        <form method="POST" action="{{ route('impersonate.leave') }}" class="shrink-0">
            @csrf
            <button type="submit" class="w-full sm:w-auto px-4 py-1.5 bg-white hover:bg-amber-50 text-amber-950 rounded-xl text-xs font-black shadow-md hover:shadow-lg transition active:scale-95 flex items-center justify-center gap-1.5">
                <span>⚡</span> Exit & Return to Admin
            </button>
        </form>
    </div>
@endif
