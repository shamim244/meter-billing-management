{{-- Primary Online PG Driver Selector --}}
<div x-show="pgEnabled" class="pt-4 border-t border-slate-900">
    <label class="block text-xs font-bold text-slate-300 mb-2">Default Active Online Gateway</label>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-lg">
        <label :class="activePgDriver === 'razorpay' ? 'border-indigo-500 bg-indigo-950/30 text-indigo-200' : 'border-slate-800 bg-slate-900 text-slate-400'" class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer hover:border-slate-700 transition">
            <input type="radio" name="active_pg_driver" value="razorpay" x-model="activePgDriver" class="text-indigo-600 focus:ring-indigo-500">
            <div>
                <div class="font-bold text-xs text-white">Razorpay Standard</div>
                <div class="text-[10px] text-slate-400">Primary instant checkout modal</div>
            </div>
        </label>

        <label :class="activePgDriver === 'cashfree' ? 'border-cyan-500 bg-cyan-950/30 text-cyan-200' : 'border-slate-800 bg-slate-900 text-slate-400'" class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer hover:border-slate-700 transition">
            <input type="radio" name="active_pg_driver" value="cashfree" x-model="activePgDriver" class="text-cyan-600 focus:ring-cyan-500">
            <div>
                <div class="font-bold text-xs text-white">Cashfree Payments</div>
                <div class="text-[10px] text-slate-400">Primary instant checkout modal</div>
            </div>
        </label>
    </div>
</div>
