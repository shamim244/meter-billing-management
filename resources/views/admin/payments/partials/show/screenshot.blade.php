@if($payment->screenshot_url)
    <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-3">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
            <span>📷</span> Uploaded Proof Screenshot
        </h2>
        <div class="bg-slate-900 p-2 rounded-xl border border-slate-800 flex items-center justify-center max-h-[500px] overflow-hidden">
            <a href="{{ $payment->screenshot_url }}" target="_blank" title="Open full size">
                <img src="{{ $payment->screenshot_url }}" alt="Proof" class="max-h-[480px] object-contain rounded-lg shadow hover:opacity-95 transition">
            </a>
        </div>
    </div>
@endif
