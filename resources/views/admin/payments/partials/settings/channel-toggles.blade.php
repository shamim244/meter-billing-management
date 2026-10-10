{{-- 1. Master Payment Channel ON / OFF Switchboard --}}
<div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-5">
    <div class="flex items-center justify-between border-b border-slate-900 pb-3">
        <div>
            <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <span>🎛️</span> 1. Payment Methods & Gateway Toggles (Turn ON / OFF)
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Toggle switches to enable or disable specific payment channels for all billing agents.</p>
        </div>
    </div>

    @include('admin.payments.partials.settings.toggles.method-cards')
    @include('admin.payments.partials.settings.toggles.driver-selector')
</div>
