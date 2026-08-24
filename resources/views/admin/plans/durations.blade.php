<x-admin-layout>
    <div x-data="planDurationsManager({{ json_encode($plan->durations) }}, {{ (float)$plan->base_price }})" class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                <button @click="openAddModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 flex items-center gap-1.5">
                    <span>➕</span> Add New Duration
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-300 text-xs font-semibold flex items-center gap-2">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-300 text-xs font-semibold flex items-center gap-2">
                <span>⚠️</span> {{ session('error') }}
            </div>
        @endif

        <!-- Plan Summary Strip -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-xl grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
            <div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Plan Name</div>
                <div class="text-sm font-black text-white mt-0.5">{{ $plan->name }}</div>
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Base Price (1m)</div>
                <div class="text-sm font-black text-emerald-400 mt-0.5">₹{{ number_format($plan->base_price, 2) }}</div>
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Included MRUs</div>
                <div class="text-sm font-bold text-slate-200 mt-0.5">{{ number_format($plan->included_mrus) }}</div>
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Consumers / Cycle</div>
                <div class="text-sm font-bold text-slate-200 mt-0.5">{{ number_format($plan->included_consumers) }}</div>
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Extra MRU Rate</div>
                <div class="text-sm font-bold text-amber-400 mt-0.5">₹{{ number_format($plan->extra_mru_rate, 2) }}</div>
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Active Subscribers</div>
                <div class="text-sm font-bold text-indigo-400 mt-0.5">{{ number_format($activeSubscribersCount) }} Agents</div>
            </div>
        </div>

        <!-- Durations Table Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span>📋</span> Configured Validity Tiers ({{ $plan->durations->count() }})
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Active durations are immediately available to billing agents during checkout.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-xs font-semibold">
                        {{ $plan->durations->where('is_active', true)->count() }} Active
                    </span>
                    <span class="px-2.5 py-1 bg-slate-800 text-slate-400 rounded-lg text-xs font-semibold">
                        {{ $plan->durations->where('is_active', false)->count() }} Disabled
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-3">Unit</th>
                            <th class="py-3 px-3">Duration & Label</th>
                            <th class="py-3 px-3">Discount %</th>
                            <th class="py-3 px-3">Payable Price (₹)</th>
                            <th class="py-3 px-3">MRU Overage Rate</th>
                            <th class="py-3 px-3">Consumer Overage Rate</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($plan->durations as $dur)
                            <tr class="hover:bg-slate-800/30 transition {{ !$dur->is_active ? 'opacity-60 bg-slate-950/40' : '' }}">
                                <!-- Unit Badge -->
                                <td class="py-3 px-3">
                                    @if($dur->duration_unit === 'day')
                                        <span class="px-2 py-0.5 bg-amber-500/10 border border-amber-500/30 text-amber-300 rounded-md text-[10px] font-bold uppercase tracking-wider">
                                            ⏱️ DAYS
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 rounded-md text-[10px] font-bold uppercase tracking-wider">
                                            📅 MONTHS
                                        </span>
                                    @endif
                                </td>

                                <!-- Duration & Label -->
                                <td class="py-3 px-3">
                                    <div class="font-bold text-white text-sm">
                                        {{ $dur->formatted_duration }}
                                    </div>
                                    @if($dur->name)
                                        <div class="text-[10px] text-slate-400">{{ $dur->name }}</div>
                                    @endif
                                </td>

                                <!-- Discount % -->
                                <td class="py-3 px-3">
                                    @if($dur->discount_percent > 0)
                                        <span class="px-2 py-0.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-bold rounded-md text-[11px]">
                                            {{ $dur->discount_percent }}% OFF
                                        </span>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>

                                <!-- Final Price -->
                                <td class="py-3 px-3 font-mono font-black text-sm text-emerald-400">
                                    ₹{{ number_format($dur->final_price, 2) }}
                                </td>

                                <!-- Extra MRU Rate -->
                                <td class="py-3 px-3 font-mono text-slate-300">
                                    @if($dur->extra_mru_rate !== null)
                                        <span class="text-amber-400 font-semibold">₹{{ number_format($dur->extra_mru_rate, 2) }}</span>
                                        <span class="text-[9px] text-slate-500 block">Custom Override</span>
                                    @else
                                        <span class="text-slate-400">₹{{ number_format($plan->extra_mru_rate, 2) }}</span>
                                        <span class="text-[9px] text-slate-500 block">Base Rate</span>
                                    @endif
                                </td>

                                <!-- Extra Consumer Rate -->
                                <td class="py-3 px-3 font-mono text-slate-300">
                                    @if($dur->extra_consumer_rate !== null)
                                        <span class="text-amber-400 font-semibold">₹{{ number_format($dur->extra_consumer_rate, 2) }}</span>
                                        <span class="text-[9px] text-slate-500 block">Custom Override</span>
                                    @else
                                        <span class="text-slate-400">₹{{ number_format($plan->extra_consumer_rate, 2) }}</span>
                                        <span class="text-[9px] text-slate-500 block">Base Rate</span>
                                    @endif
                                </td>

                                <!-- Status Toggle -->
                                <td class="py-3 px-3 text-center">
                                    <form method="POST" action="{{ route('admin.plans.durations.toggle', [$plan, $dur]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" title="Click to Toggle Active State" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase transition flex items-center gap-1 mx-auto {{ $dur->is_active ? 'bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 border border-slate-700' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $dur->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                            {{ $dur->is_active ? 'ACTIVE' : 'DISABLED' }}
                                        </button>
                                    </form>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="openEditModal({{ json_encode($dur) }})" class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition border border-slate-700" title="Edit Duration">
                                            ✏️
                                        </button>

                                        @if($plan->durations->count() > 1)
                                            <form method="POST" action="{{ route('admin.plans.durations.destroy', [$plan, $dur]) }}" onsubmit="return confirm('Are you sure you want to delete this duration tier?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 rounded-lg transition border border-rose-500/20" title="Delete Duration">
                                                    🗑️
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10 text-slate-500">
                                    No durations configured for this plan. Click "+ Add New Duration" to get started.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL: Add New Duration -->
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.outside="showAddModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl p-6 space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>➕</span> Add New Duration Tier
                    </h3>
                    <button @click="showAddModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.plans.durations.store', $plan) }}" class="space-y-4">
                    @csrf

                    <!-- 1. Duration Unit Selection (Days vs Months) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Duration Unit Type *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition" :class="form.unit === 'month' ? 'bg-indigo-500/10 border-indigo-500 text-white font-bold' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'">
                                <input type="radio" name="duration_unit" value="month" x-model="form.unit" @change="recalculatePrice()" class="text-indigo-600 focus:ring-0">
                                <div>
                                    <div class="text-xs">📅 Month-Wise</div>
                                    <div class="text-[10px] text-slate-400 font-normal">e.g. 1, 3, 6, 12, 24 months</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition" :class="form.unit === 'day' ? 'bg-amber-500/10 border-amber-500 text-white font-bold' : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'">
                                <input type="radio" name="duration_unit" value="day" x-model="form.unit" @change="recalculatePrice()" class="text-amber-500 focus:ring-0">
                                <div>
                                    <div class="text-xs">⏱️ Day-Wise</div>
                                    <div class="text-[10px] text-slate-400 font-normal">e.g. 7, 14, 15, 30, 45 days</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- 2. Value & Optional Label -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                Duration Number <span class="text-rose-400">*</span>
                            </label>
                            <input type="number" min="1" max="3650" name="duration_value" x-model.number="form.value" @input="recalculatePrice()" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono font-bold">
                            <span class="text-[10px] text-slate-500 mt-1 block" x-text="'Validity: ' + form.value + ' ' + (form.unit === 'day' ? 'Day(s)' : 'Month(s)')"></span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Display Label (Optional)</label>
                            <input type="text" name="name" x-model="form.name" placeholder="e.g. 7 Days Trial, Annual" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- 3. Pricing & Discounts -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Discount %</label>
                            <input type="number" step="0.1" min="0" max="100" name="discount_percent" x-model.number="form.discount" @input="recalculatePrice()" placeholder="0" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Final Payable Price (₹) <span class="text-rose-400">*</span></label>
                            <input type="number" step="0.01" min="0" name="final_price" x-model.number="form.price" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-emerald-400 font-bold p-2.5 focus:ring-indigo-500 font-mono">
                        </div>
                    </div>

                    <!-- 4. Optional Overage Overrides -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-800/80">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Extra MRU Rate (₹)</label>
                            <input type="number" step="0.01" min="0" name="extra_mru_rate" x-model="form.extraMru" placeholder="Base (₹{{ $plan->extra_mru_rate }})" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Extra CA Rate (₹)</label>
                            <input type="number" step="0.01" min="0" name="extra_consumer_rate" x-model="form.extraConsumer" placeholder="Base (₹{{ $plan->extra_consumer_rate }})" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        </div>
                    </div>

                    <!-- 5. Active Status Toggle -->
                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="is_active" value="1" x-model="form.isActive" id="add_is_active" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0">
                        <label for="add_is_active" class="text-xs font-semibold text-slate-300 cursor-pointer">
                            Enable immediately for subscriber checkouts
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30">
                            Save Duration Tier
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Edit Duration -->
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.outside="showEditModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl p-6 space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>✏️</span> Edit Duration — <span x-text="editForm.title"></span>
                    </h3>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
                </div>

                <form method="POST" :action="'/admin/plans/{{ $plan->id }}/durations/' + editForm.id" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Display Label (Optional)</label>
                        <input type="text" name="name" x-model="editForm.name" placeholder="e.g. 7 Days Trial, Annual" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Discount %</label>
                            <input type="number" step="0.1" min="0" max="100" name="discount_percent" x-model.number="editForm.discount" @input="recalculateEditPrice()" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Final Payable Price (₹) *</label>
                            <input type="number" step="0.01" min="0" name="final_price" x-model.number="editForm.price" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-emerald-400 font-bold p-2.5 focus:ring-indigo-500 font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-800/80">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Extra MRU Rate (₹)</label>
                            <input type="number" step="0.01" min="0" name="extra_mru_rate" x-model="editForm.extraMru" placeholder="Base (₹{{ $plan->extra_mru_rate }})" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Extra CA Rate (₹)</label>
                            <input type="number" step="0.01" min="0" name="extra_consumer_rate" x-model="editForm.extraConsumer" placeholder="Base (₹{{ $plan->extra_consumer_rate }})" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="is_active" value="1" x-model="editForm.isActive" id="edit_is_active" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0">
                        <label for="edit_is_active" class="text-xs font-semibold text-slate-300 cursor-pointer">
                            Active & visible to agents
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30">
                            Update Duration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function planDurationsManager(durationsData, basePriceVal) {
            return {
                basePrice: basePriceVal || 0,
                showAddModal: false,
                showEditModal: false,
                form: {
                    unit: 'month',
                    value: 1,
                    name: '',
                    discount: 0,
                    price: 0,
                    extraMru: '',
                    extraConsumer: '',
                    isActive: true,
                },
                editForm: {
                    id: null,
                    title: '',
                    unit: 'month',
                    value: 1,
                    name: '',
                    discount: 0,
                    price: 0,
                    extraMru: '',
                    extraConsumer: '',
                    isActive: true,
                },

                openAddModal() {
                    this.form = {
                        unit: 'month',
                        value: 1,
                        name: '',
                        discount: 0,
                        price: this.basePrice,
                        extraMru: '',
                        extraConsumer: '',
                        isActive: true,
                    };
                    this.recalculatePrice();
                    this.showAddModal = true;
                },

                openEditModal(dur) {
                    this.editForm = {
                        id: dur.id,
                        title: dur.name || (dur.duration_value + ' ' + (dur.duration_unit === 'day' ? 'Days' : 'Months')),
                        unit: dur.duration_unit || 'month',
                        value: dur.duration_value || dur.duration_months || 1,
                        name: dur.name || '',
                        discount: parseFloat(dur.discount_percent) || 0,
                        price: parseFloat(dur.final_price) || 0,
                        extraMru: dur.extra_mru_rate || '',
                        extraConsumer: dur.extra_consumer_rate || '',
                        isActive: Boolean(dur.is_active),
                    };
                    this.showEditModal = true;
                },

                recalculatePrice() {
                    const discount = Math.min(100, Math.max(0, this.form.discount || 0));
                    const val = Math.max(1, this.form.value || 1);
                    if (this.form.unit === 'day') {
                        this.form.price = parseFloat(((this.basePrice / 30) * val * (1 - (discount / 100))).toFixed(2));
                    } else {
                        this.form.price = parseFloat((this.basePrice * val * (1 - (discount / 100))).toFixed(2));
                    }
                },

                recalculateEditPrice() {
                    const discount = Math.min(100, Math.max(0, this.editForm.discount || 0));
                    const val = Math.max(1, this.editForm.value || 1);
                    if (this.editForm.unit === 'day') {
                        this.editForm.price = parseFloat(((this.basePrice / 30) * val * (1 - (discount / 100))).toFixed(2));
                    } else {
                        this.editForm.price = parseFloat((this.basePrice * val * (1 - (discount / 100))).toFixed(2));
                    }
                }
            };
        }
    </script>
</x-admin-layout>
