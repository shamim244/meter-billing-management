<!-- Section 3: Customization & Security -->
<div class="space-y-1">
    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">
        Preferences & Security
    </div>

    <a href="{{ route('user-panel.shortcuts') }}" 
       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user-panel.shortcuts') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
        <span class="text-base">⌨️</span>
        <span>Keyboard Shortcuts</span>
    </a>

    <a href="{{ route('user-panel.preferences') }}" 
       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user-panel.preferences') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
        <span class="text-base">⚙️</span>
        <span>General Preferences</span>
    </a>

    <a href="{{ route('user-panel.backup') }}" 
       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user-panel.backup*') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
        <span class="text-base">💾</span>
        <span>Data Export & Backup</span>
    </a>

    <a href="{{ route('user-panel.profile') }}" 
       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user-panel.profile') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
        <span class="text-base">👤</span>
        <span>Profile & Security</span>
    </a>

    <a href="{{ route('user-panel.api-keys') }}" 
       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user-panel.api-keys*') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
        <span class="text-base">🔑</span>
        <div class="flex-1 flex items-center justify-between">
            <span>API Keys & Tokens</span>
            @php $activeKeyCount = Auth::user()->apiKeys()->count(); @endphp
            @if($activeKeyCount > 0)
                <span class="text-[9px] font-mono font-bold px-1.5 py-0.5 rounded-full {{ request()->routeIs('user-panel.api-keys*') ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                    {{ $activeKeyCount }}
                </span>
            @endif
        </div>
    </a>
</div>
