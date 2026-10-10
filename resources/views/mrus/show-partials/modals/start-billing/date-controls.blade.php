<!-- Billing Month & Year Selectors -->
<div class="grid grid-cols-2 gap-3">
    <!-- Billing Month -->
    <div>
        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Billing Month</label>
        <select x-model="cycleMonth" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
            <option value="1">January</option>
            <option value="2">February</option>
            <option value="3">March</option>
            <option value="4">April</option>
            <option value="5">May</option>
            <option value="6">June</option>
            <option value="7">July</option>
            <option value="8">August</option>
            <option value="9">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
        </select>
    </div>

    <!-- Billing Year -->
    <div>
        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Billing Year</label>
        <select x-model="cycleYear" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
            @php
                $currYear = (int) date('Y');
                $availableYears = range(max(2020, $currYear - 3), $currYear + 5);
                rsort($availableYears);
            @endphp
            @foreach($availableYears as $yr)
                <option value="{{ $yr }}">{{ $yr }}</option>
            @endforeach
        </select>
    </div>
</div>

<!-- Tip Box -->
<div class="p-3.5 bg-blue-50/60 dark:bg-blue-950/40 rounded-2xl border border-blue-100 dark:border-blue-900/60 text-[11px] text-blue-800 dark:text-cyan-300 leading-relaxed">
    💡 <strong>Tip:</strong> Choose <strong>"Create Cycle Only"</strong> to initialize the billing session workspace immediately with preceding readings, or <strong>"Create & Download All"</strong> to fetch official PDFs right away.
</div>
