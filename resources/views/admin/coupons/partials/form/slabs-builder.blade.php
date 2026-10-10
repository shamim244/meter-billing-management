<div x-show="type === 'topup_bonus'" class="space-y-4 bg-slate-900/50 p-5 rounded-2xl border border-slate-800">
    <div class="flex items-center justify-between">
        <h3 class="text-xs font-bold uppercase tracking-wider text-cyan-400">Recharge Amount Bonus Slabs</h3>
        <button type="button" @click="addSlab()" class="px-3 py-1 bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-300 border border-cyan-500/30 rounded-lg text-xs font-bold transition">
            + Add Tier Slab
        </button>
    </div>

    <div class="space-y-2.5">
        <template x-for="(slab, index) in slabs" :key="index">
            <div class="grid grid-cols-12 gap-2 items-center bg-slate-950 p-3 rounded-xl border border-slate-800">
                <div class="col-span-4">
                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Min Amount (₹)</label>
                    <input type="number" :name="'slabs['+index+'][min_amount]'" x-model="slab.min_amount" :disabled="type !== 'topup_bonus'" min="0" class="w-full text-xs font-mono bg-slate-900 border-slate-800 rounded-lg py-1.5 px-2 text-white">
                </div>
                <div class="col-span-4">
                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Max Amount (₹)</label>
                    <input type="number" :name="'slabs['+index+'][max_amount]'" x-model="slab.max_amount" :disabled="type !== 'topup_bonus'" placeholder="No limit" class="w-full text-xs font-mono bg-slate-900 border-slate-800 rounded-lg py-1.5 px-2 text-white">
                </div>
                <div class="col-span-3">
                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Bonus %</label>
                    <input type="number" step="0.1" :name="'slabs['+index+'][bonus_percent]'" x-model="slab.bonus_percent" :disabled="type !== 'topup_bonus'" min="0.01" max="100" class="w-full text-xs font-mono font-bold bg-slate-900 border-slate-800 rounded-lg py-1.5 px-2 text-emerald-400">
                </div>
                <div class="col-span-1 text-center pt-3">
                    <button type="button" @click="removeSlab(index)" x-show="slabs.length > 1" class="text-rose-400 hover:text-rose-300 p-1 font-bold">✕</button>
                </div>
            </div>
        </template>
    </div>
</div>
