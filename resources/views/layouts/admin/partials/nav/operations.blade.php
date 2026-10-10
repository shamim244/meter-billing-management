<!-- Section 1: Operations & Agents -->
<div class="space-y-1">
    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-500 tracking-wider">
        Operations & Agents
    </div>

    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">📊</span>
        <span>Dashboard</span>
    </a>

    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.users.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">👥</span>
        <span>Users & Billing Agents</span>
    </a>

    <a href="{{ route('admin.mrus.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.mrus.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">🗂️</span>
        <span>MRU Master List</span>
    </a>

    <a href="{{ route('admin.bills.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.bills.index') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">📑</span>
        <span>All Bills Inspector</span>
    </a>

    <a href="{{ route('admin.bills.engine-settings') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.bills.engine-settings*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">⚡</span>
        <span>Billing Engine Settings</span>
    </a>

    <a href="{{ route('admin.compression.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.compression.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">🗜️</span>
        <span>Adaptive Compression</span>
    </a>
</div>
