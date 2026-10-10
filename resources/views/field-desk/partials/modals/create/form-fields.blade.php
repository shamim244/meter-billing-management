<!-- CA Number & Mobile Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Consumer CA Number *</label>
        <input type="text"
               x-model="form.ca_number"
               @change="if(form.ca_number) openCreateModal(form.ca_number)"
               required
               placeholder="e.g. 10230041576"
               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-emerald-500">
    </div>
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Consumer Mobile (Optional)</label>
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs font-mono">📱 +91</span>
            <input type="tel"
                   x-model="form.mobile"
                   maxlength="10"
                   placeholder="10-digit mobile"
                   class="w-full pl-14 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-emerald-500">
        </div>
    </div>
</div>

<!-- Category Picker -->
<div>
    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Action Category *</label>
    <select x-model="form.category_id" required class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
        @endforeach
    </select>
</div>

<!-- Target Date & Quick Date Presets -->
<div>
    <div class="flex items-center justify-between mb-1">
        <label class="font-bold text-slate-700 dark:text-slate-300">Target Date *</label>
        <!-- Quick Presets -->
        <div class="flex items-center gap-1">
            <button type="button" @click="setDatePreset(0)" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Today</button>
            <button type="button" @click="setDatePreset(1)" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">+1d</button>
            <button type="button" @click="setDatePreset(2)" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">+2d</button>
            <button type="button" @click="setDatePreset(5)" class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">+5d</button>
        </div>
    </div>
    <input type="date"
           x-model="form.target_date"
           required
           class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
</div>

<!-- Priority & MRU Grid -->
<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Priority</label>
        <select x-model="form.priority" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
            <option value="low">Low</option>
        </select>
    </div>
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">MRU (Optional)</label>
        <select x-model="form.mru_id" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            <option value="">Auto-detect / None</option>
            @foreach($mrus as $m)
                <option value="{{ $m->id }}">{{ $m->code }} - {{ Str::limit($m->name, 12) }}</option>
            @endforeach
        </select>
    </div>
</div>

<!-- Promised Amount & Payment Mode -->
<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Amount (₹)</label>
        <input type="number"
               step="0.01"
               x-model="form.target_amount"
               placeholder="0.00"
               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
    </div>
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Expected Mode</label>
        <select x-model="form.payment_mode" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            <option value="">Select Mode...</option>
            <option value="cash">Cash in hand</option>
            <option value="upi_phonepe">UPI / PhonePe</option>
            <option value="online">Online Portal</option>
            <option value="office">Subdivision Office</option>
        </select>
    </div>
</div>

<!-- Private Note / Dossier Details -->
<div>
    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Field Dossier Note</label>
    <textarea x-model="form.private_note"
              rows="2"
              placeholder="e.g. PhonePe: 9876543210 • Salary on 10th • 2nd house near temple"
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
</div>
