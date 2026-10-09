<!-- Breadcrumb & Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
            <a href="{{ route('admin.plans.index') }}" class="hover:text-indigo-400 transition">Plans</a>
            <span>/</span>
            <span class="text-slate-200">{{ $plan->name }}</span>
            <span>/</span>
            <span class="text-indigo-400">Durations</span>
        </div>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>⏳</span> Duration Tiers & Pricing — {{ $plan->name }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-400 mt-1">
            Manage validity periods, day-wise trials, month-wise commitments, duration discounts, and toggle tier availability.
        </p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.plans.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700/60">
            ← Back to Plans
        </a>
        <button @click="openAddModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 flex items-center gap-1.5 cursor-pointer">
            <span>➕</span> Add New Duration
        </button>
    </div>
</div>
