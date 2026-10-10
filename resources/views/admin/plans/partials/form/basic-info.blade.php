<div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-xl">
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
            <span>📦</span> 1. Plan Details & Included Quotas
        </h2>
        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-300 cursor-pointer flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }} class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0">
                Active & Publicly Visible
            </label>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-300 mb-1">Plan Name <span class="text-rose-400">*</span></label>
            <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}" required placeholder="e.g. Starter Operator" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-bold">
        </div>

        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
            <textarea name="description" rows="2" placeholder="Brief outline of who this tier is designed for..." class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">{{ old('description', $plan->description ?? '') }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Included MRUs Quota <span class="text-rose-400">*</span></label>
            <input type="number" name="included_mrus" min="0" value="{{ old('included_mrus', $plan->included_mrus ?? 2) }}" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Included Consumers Quota / Cycle <span class="text-rose-400">*</span></label>
            <input type="number" name="included_consumers" min="0" value="{{ old('included_consumers', $plan->included_consumers ?? 2500) }}" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Grace Period Days</label>
            <input type="number" name="grace_period_days" min="0" max="90" value="{{ old('grace_period_days', $plan->grace_period_days ?? '') }}" placeholder="Platform Default (3)" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
        </div>
    </div>
</div>
