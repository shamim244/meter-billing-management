<div>
    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Date *</label>
    <input type="date"
           x-model="editForm.target_date"
           required
           class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
</div>

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
        <select x-model="editForm.category_id" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Priority</label>
        <select x-model="editForm.priority" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
            <option value="low">Low</option>
        </select>
    </div>
</div>

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Amount (₹)</label>
        <input type="number"
               step="0.01"
               x-model="editForm.target_amount"
               class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
    </div>
    <div>
        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Payment Mode</label>
        <select x-model="editForm.payment_mode" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
            <option value="">Select Mode...</option>
            <option value="cash">Cash in hand</option>
            <option value="upi_phonepe">UPI / PhonePe</option>
            <option value="online">Online Portal</option>
            <option value="office">Subdivision Office</option>
        </select>
    </div>
</div>

<!-- Mobile Input -->
<div>
    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Consumer Mobile</label>
    <div class="relative">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs font-mono">📱 +91</span>
        <input type="tel"
               x-model="editForm.mobile"
               maxlength="10"
               placeholder="10-digit mobile number"
               class="w-full pl-14 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono">
    </div>
</div>

<div>
    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Private Field Note</label>
    <textarea x-model="editForm.private_note"
              rows="2"
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
</div>
