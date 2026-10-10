<!-- Section 4: Marketing & Growth -->
<div class="space-y-1">
    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-500 tracking-wider">
        Marketing & Growth
    </div>

    <a href="{{ route('admin.coupons.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.coupons.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">🎟️</span>
        <span>Coupon Campaigns</span>
    </a>

    <a href="{{ route('admin.referrals.settings') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.referrals.*') ? 'bg-purple-600 text-white font-bold shadow-lg shadow-purple-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
        <span class="text-base">🎁</span>
        <span>Refer & Earn Program</span>
    </a>
</div>
