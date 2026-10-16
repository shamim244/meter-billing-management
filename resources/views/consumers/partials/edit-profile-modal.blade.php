<!-- Edit Consumer Profile Modal -->
<div x-show="showEditProfileModal" 
     x-cloak 
     id="edit-consumer-profile-modal"
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="showEditProfileModal = false" 
         class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative text-left">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="text-xl">✏️</span>
                <div>
                    <h3 class="text-sm font-bold text-white">Edit Consumer Specifications</h3>
                    <p class="text-[11px] text-slate-400 font-mono">CA: {{ $account->ca_number }}</p>
                </div>
            </div>
            <button type="button" @click="showEditProfileModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg">✕</button>
        </div>

        <!-- Form -->
        <form @submit.prevent="saveProfile()" class="space-y-3.5 text-xs">
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Consumer Name</label>
                    <input type="text" x-model="profileForm.consumer_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-white">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Father / Husband Name</label>
                    <input type="text" x-model="profileForm.father_name" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Mobile Number</label>
                    <input type="text" x-model="profileForm.mobile" placeholder="10-digit mobile" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-white font-mono">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Meter Number</label>
                    <input type="text" x-model="profileForm.meter_no" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Tariff Category</label>
                    <input type="text" x-model="profileForm.tariff_category" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-white font-mono">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Billing Basis</label>
                    <select x-model="profileForm.billing_basis" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-white font-mono">
                        <option value="OK">OK (Normal)</option>
                        <option value="LK">LK (Locked)</option>
                        <option value="MD">MD (Defective)</option>
                        <option value="PL">PL (Power Line)</option>
                        <option value="RN">RN (Reading N/A)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Safe Monthly Ceiling Override (kWh)</label>
                    <input type="number" x-model="profileForm.custom_safe_ceiling" min="0" placeholder="Default by tariff" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-white font-mono">
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-300">Gap Recovery Mode</label>
                    <select x-model="profileForm.gap_recovery_mode" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-white font-mono">
                        <option value="auto">Auto Standard</option>
                        <option value="hold_exact">+0 Hold Exact</option>
                        <option value="hold_advance">+1 Unit Advance</option>
                        <option value="amortize_safe">Safe Amortize</option>
                    </select>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-[11px] font-bold text-slate-300">Address / Location</label>
                <input type="text" x-model="profileForm.address" placeholder="Village / Area / Landmark" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-white">
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800">
                <button type="button" @click="showEditProfileModal = false" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white">Cancel</button>
                <button type="submit" 
                        :disabled="savingProfile"
                        class="px-4 py-1.5 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-500 text-white flex items-center gap-1.5 transition active:scale-95 disabled:opacity-50">
                    <span x-show="savingProfile" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>
