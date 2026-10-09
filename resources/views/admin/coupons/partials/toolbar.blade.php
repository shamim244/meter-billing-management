{{-- Filters & Actions Toolbar --}}
<div class="bg-slate-950 p-4 rounded-3xl border border-slate-800 shadow-lg flex flex-col lg:flex-row lg:items-center justify-between gap-3">
    <form method="GET" action="{{ route('admin.coupons.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
        <div class="flex-1 min-w-[180px]">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search coupon code..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500 uppercase">
        </div>

        <select name="type" class="text-xs bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-white focus:ring-indigo-500">
            <option value="all" {{ $typeFilter === 'all' ? 'selected' : '' }}>All Types</option>
            <option value="subscription_discount" {{ $typeFilter === 'subscription_discount' ? 'selected' : '' }}>Subscription Discount</option>
            <option value="topup_bonus" {{ $typeFilter === 'topup_bonus' ? 'selected' : '' }}>Top-Up Bonus (Slabs)</option>
        </select>

        <select name="status" class="text-xs bg-slate-900 border-slate-800 rounded-xl px-3 py-2 text-white focus:ring-indigo-500">
            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Statuses</option>
            <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active Only</option>
            <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
        </select>

        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition shrink-0">
            Filter
        </button>

        @if(!empty($search) || $typeFilter !== 'all' || $statusFilter !== 'all')
            <a href="{{ route('admin.coupons.index') }}" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-slate-400 rounded-xl text-xs font-medium transition shrink-0">
                Clear
            </a>
        @endif
    </form>

    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('admin.coupons.create') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/20 transition text-center shrink-0">
            <span>+</span> Create Coupon Code
        </a>
    </div>
</div>
