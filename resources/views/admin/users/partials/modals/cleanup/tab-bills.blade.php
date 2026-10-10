<!-- TAB 3: Clean Consumer Bill Records -->
<div x-show="cleanupTab === 'bills'" class="space-y-4" x-cloak>
    <div class="p-3 rounded-xl bg-cyan-950/30 border border-cyan-500/20 text-xs text-cyan-200 leading-relaxed">
        👥 <strong>Consumer Records Flush:</strong> Deletes consumer bill rows for a specific cycle or status. MRU container definitions remain intact for fresh billing cycles.
    </div>

    <form method="POST" action="{{ route('admin.users.clean_bills', $user) }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div>
                <label class="block text-[11px] font-bold text-slate-400 mb-1">Target MRU (Optional):</label>
                <select name="mru_id" x-model="billMruId" class="w-full text-xs bg-slate-950 border-slate-700 rounded-xl text-white py-2 px-3 focus:ring-cyan-500">
                    <option value="">All MRUs</option>
                    @foreach($mrus as $mru)
                        <option value="{{ $mru->id }}">{{ $mru->code }} - {{ $mru->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-400 mb-1">Review Status Filter:</label>
                <select name="status_scope" x-model="billStatus" class="w-full text-xs bg-slate-950 border-slate-700 rounded-xl text-white py-2 px-3 focus:ring-cyan-500">
                    <option value="all">All Bill Records</option>
                    <option value="doubt">Doubt Review Bills Only</option>
                    <option value="critical">Critical Review Bills Only</option>
                    <option value="unparsed">Unparsed / Draft Bills Only</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-400 mb-1">Billing Month (Optional):</label>
                <select name="billing_month" x-model="billMonth" class="w-full text-xs bg-slate-950 border-slate-700 rounded-xl text-white py-2 px-3 focus:ring-cyan-500">
                    <option value="">Any Month</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }} ({{ sprintf('%02d', $m) }})</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-400 mb-1">Billing Year (Optional):</label>
                <select name="billing_year" x-model="billYear" class="w-full text-xs bg-slate-950 border-slate-700 rounded-xl text-white py-2 px-3 focus:ring-cyan-500">
                    <option value="">Any Year</option>
                    @for($y = (int) now()->year; $y >= 2024; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
            <button type="button" @click="showCleanupModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" onclick="return confirm('Flush consumer bill records matching the selected filters?');" class="px-5 py-2 text-xs font-bold rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white shadow">
                Flush Filtered Bill Records
            </button>
        </div>
    </form>
</div>
