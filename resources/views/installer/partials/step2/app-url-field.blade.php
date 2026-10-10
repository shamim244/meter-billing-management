<!-- App URL Field -->
<div>
    <label class="block text-xs font-bold text-slate-300 mb-1">Application URL</label>
    <input type="url" name="app_url" value="{{ old('app_url', $currentUrl) }}" required class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
    <span class="text-[10px] text-slate-500 mt-1 block">Full URL of this website (used for generating links, QR codes, and API endpoints).</span>
</div>
