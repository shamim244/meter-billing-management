<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-4">
    <a href="{{ route('admin.issues.index', ['status' => 'all']) }}"
       class="p-4 rounded-2xl border transition {{ $status === 'all' ? 'bg-indigo-950/40 border-indigo-500 shadow-lg' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">All Reports</span>
        <div class="text-2xl font-black text-white font-mono mt-1">{{ $counts['all'] }}</div>
    </a>

    <a href="{{ route('admin.issues.index', ['status' => 'pending']) }}"
       class="p-4 rounded-2xl border transition {{ $status === 'pending' ? 'bg-amber-950/40 border-amber-500 shadow-lg' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider">Pending Triage</span>
            @if($counts['pending'] > 0)
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
            @endif
        </div>
        <div class="text-2xl font-black text-amber-400 font-mono mt-1">{{ $counts['pending'] }}</div>
    </a>

    <a href="{{ route('admin.issues.index', ['status' => 'verified']) }}"
       class="p-4 rounded-2xl border transition {{ $status === 'verified' ? 'bg-rose-950/40 border-rose-500 shadow-lg' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
        <span class="text-[11px] font-bold text-rose-400 uppercase tracking-wider">Verified Bugs</span>
        <div class="text-2xl font-black text-rose-400 font-mono mt-1">{{ $counts['verified'] }}</div>
    </a>

    <a href="{{ route('admin.issues.index', ['status' => 'resolved']) }}"
       class="p-4 rounded-2xl border transition {{ $status === 'resolved' ? 'bg-emerald-950/40 border-emerald-500 shadow-lg' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
        <span class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Resolved</span>
        <div class="text-2xl font-black text-emerald-400 font-mono mt-1">{{ $counts['resolved'] }}</div>
    </a>

    <a href="{{ route('admin.issues.index', ['status' => 'spam']) }}"
       class="p-4 rounded-2xl border transition {{ $status === 'spam' ? 'bg-slate-900 border-slate-600 shadow-lg' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700' }}">
        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Spam / Dismissed</span>
        <div class="text-2xl font-black text-slate-500 font-mono mt-1">{{ $counts['spam'] }}</div>
    </a>
</div>
