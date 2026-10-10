<!-- Section 2: Growth & Rewards -->
<div class="space-y-1">
    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">
        Growth & Rewards
    </div>

    <a href="{{ route('referrals.index') }}" 
       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('referrals.*') ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-500/20' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
        <span class="text-base">🎁</span>
        <span>Refer & Earn Hub</span>
    </a>
</div>
