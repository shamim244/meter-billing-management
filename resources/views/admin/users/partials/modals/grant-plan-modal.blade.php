<!-- MODAL 1: Grant Plan / Extend Validity -->
<div x-show="showGrantModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="showGrantModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>🎁</span> Grant Plan / Extend Validity
            </h3>
            <button type="button" @click="showGrantModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.users.grant-plan', $user) }}" class="space-y-4">
            @csrf
            
            <!-- Mode Switch -->
            <div class="grid grid-cols-2 gap-2 p-1 bg-slate-950 rounded-xl border border-slate-800 text-xs">
                <button type="button" @click="grantMode = 'new_plan'" :class="grantMode === 'new_plan' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-white'" class="py-2 rounded-lg transition text-center">
                    Assign New Plan
                </button>
                <button type="button" @click="grantMode = 'extend_validity'" :class="grantMode === 'extend_validity' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-white'" class="py-2 rounded-lg transition text-center">
                    + Add Days to Expiry
                </button>
            </div>
            <input type="hidden" name="grant_mode" :value="grantMode">

            <!-- Mode A: Assign New Plan -->
            <div x-show="grantMode === 'new_plan'" class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Select Subscription Plan</label>
                    <select name="plan_id" x-model="selectedPlanId" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3">
                        @foreach($availablePlans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->included_mrus }} MRUs / {{ number_format($plan->included_consumers) }} CAs)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Select Validity Duration</label>
                    @foreach($availablePlans as $plan)
                        <div x-show="selectedPlanId == '{{ $plan->id }}'" class="space-y-1">
                            <select name="duration_id" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3" :disabled="selectedPlanId != '{{ $plan->id }}'">
                                @foreach($plan->activeDurations as $dur)
                                    <option value="{{ $dur->id }}">{{ $dur->formatted_duration }} (Standard: ₹{{ number_format($dur->final_price, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Mode B: Extend Validity Days -->
            <div x-show="grantMode === 'extend_validity'" class="space-y-3">
                <label class="block text-xs font-bold text-slate-300">Days to add to active subscription</label>
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" @click="$refs.daysInput.value = 30" class="py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-bold text-white transition">+30 Days</button>
                    <button type="button" @click="$refs.daysInput.value = 60" class="py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-bold text-white transition">+60 Days</button>
                    <button type="button" @click="$refs.daysInput.value = 90" class="py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-bold text-white transition">+90 Days</button>
                    <button type="button" @click="$refs.daysInput.value = 365" class="py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-bold text-white transition">+1 Year</button>
                </div>
                <input x-ref="daysInput" type="number" name="days_to_add" value="30" min="1" max="365" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 font-mono" placeholder="Custom days (e.g. 45)">
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                <button type="button" @click="showGrantModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow">Apply Grant / Extension</button>
            </div>
        </form>
    </div>
</div>
