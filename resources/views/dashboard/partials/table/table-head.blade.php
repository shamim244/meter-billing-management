<thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">
    <tr>
        <th class="py-3.5 px-3 cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('ca_number')">
            <div class="inline-flex items-center gap-1">
                <span>Consumer</span>
                <span class="text-[10px]" x-show="sortCol === 'ca_number'" x-text="sortAsc ? '▲' : '▼'"></span>
            </div>
        </th>
        <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('billing_basis')">
            <div class="inline-flex items-center justify-center gap-1">
                <span>Basis</span>
                <span class="text-[10px]" x-show="sortCol === 'billing_basis' || sortCol === 'basis_priority'" x-text="sortAsc ? '▲' : '▼'"></span>
            </div>
        </th>
        <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('working_reading')">
            <div class="inline-flex items-center justify-center gap-1">
                <span>✍️ Working Reading</span>
                <span class="text-[10px]" x-show="sortCol === 'working_reading'" x-text="sortAsc ? '▲' : '▼'"></span>
            </div>
        </th>
        <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('previous_reading')">
            <div class="inline-flex items-center justify-center gap-1">
                <span>📅 Prev (DB)</span>
                <span class="text-[10px]" x-show="sortCol === 'previous_reading'" x-text="sortAsc ? '▲' : '▼'"></span>
            </div>
        </th>
        <th class="py-3.5 px-3 text-center">📊 Avg (kWh)</th>
        <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('current_reading')">
            <div class="inline-flex items-center justify-center gap-1">
                <span>📄 PDF Read</span>
                <span class="text-[10px]" x-show="sortCol === 'current_reading'" x-text="sortAsc ? '▲' : '▼'"></span>
            </div>
        </th>
        <th class="py-3.5 px-3 text-right cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('amount')">
            <div class="inline-flex items-center justify-end gap-1">
                <span>Amount</span>
                <span class="text-[10px]" x-show="sortCol === 'amount' || sortCol === 'total_amount'" x-text="sortAsc ? '▲' : '▼'"></span>
            </div>
        </th>
        <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('bill_month')">
            <div class="inline-flex items-center justify-center gap-1">
                <span>Month</span>
                <span class="text-[10px]" x-show="sortCol === 'bill_month'" x-text="sortAsc ? '▲' : '▼'"></span>
            </div>
        </th>
        <th class="py-3.5 px-3 text-center cursor-pointer select-none hover:text-blue-600 dark:hover:text-cyan-400" @click="toggleSort('review_status')">
            <div class="inline-flex items-center justify-center gap-1">
                <span>Status</span>
                <span class="text-[10px]" x-show="sortCol === 'review_status' || sortCol === 'status'" x-text="sortAsc ? '▲' : '▼'"></span>
            </div>
        </th>
        <th class="py-3.5 px-3 text-center">Tag</th>
        <th class="py-3.5 px-3">Remark</th>
        <th class="py-3.5 px-3 text-center">PDF</th>
    </tr>
</thead>
