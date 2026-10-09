<!-- Standard Doubt & Critical Reason Codes Dictionary -->
<section id="reason-codes" class="space-y-6">
    <div class="border-b border-slate-800 pb-4">
        <h2 class="text-2xl font-black text-white tracking-tight">Standard Reason Code Dictionary</h2>
        <p class="text-xs text-slate-400 mt-1">Pass these standard codes in <code class="font-mono text-cyan-300">reason_code</code> to maintain structured reporting in the web dashboard.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Doubt Codes -->
        <div class="glass-panel p-6 rounded-3xl space-y-4">
            <h3 class="text-sm font-bold text-amber-400 flex items-center gap-2">
                <span>🟡</span>
                <span>Doubt Reason Codes (Suspicious / Re-check)</span>
            </h3>
            <ul class="space-y-2.5 text-xs">
                <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                    <code class="font-mono text-cyan-300 font-bold">PREMISES_LOCKED</code>
                    <span class="text-slate-400">House locked / closed</span>
                </li>
                <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                    <code class="font-mono text-cyan-300 font-bold">SUSPICIOUS_READING</code>
                    <span class="text-slate-400">Abnormal reading spike</span>
                </li>
                <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                    <code class="font-mono text-cyan-300 font-bold">SUSPICIOUS_AMOUNT</code>
                    <span class="text-slate-400">High arrears disputed</span>
                </li>
                <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                    <code class="font-mono text-cyan-300 font-bold">PREV_MONTH_MISMATCH</code>
                    <span class="text-slate-400">Dial baseline mismatch</span>
                </li>
                <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                    <code class="font-mono text-cyan-300 font-bold">OWNER_RECHECK_REQUEST</code>
                    <span class="text-slate-400">Consumer re-verification</span>
                </li>
            </ul>
        </div>

        <!-- Critical Codes -->
        <div class="glass-panel p-6 rounded-3xl space-y-4">
            <h3 class="text-sm font-bold text-rose-400 flex items-center gap-2">
                <span>🔴</span>
                <span>Critical Reason Codes (Unworkable / Fault)</span>
            </h3>
            <ul class="space-y-2.5 text-xs">
                <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                    <code class="font-mono text-rose-300 font-bold">METER_BURNT_DEAD</code>
                    <span class="text-slate-400">Black/burnt display</span>
                </li>
                <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                    <code class="font-mono text-rose-300 font-bold">METER_TAMPERED_BYPASS</code>
                    <span class="text-slate-400">Seal broken / direct line</span>
                </li>
                <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                    <code class="font-mono text-rose-300 font-bold">METER_MISSING_STOLEN</code>
                    <span class="text-slate-400">Meter stolen / not found</span>
                </li>
                <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                    <code class="font-mono text-rose-300 font-bold">PREMISES_DEMOLISHED</code>
                    <span class="text-slate-400">Building demolished</span>
                </li>
                <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                    <code class="font-mono text-rose-300 font-bold">JE_INTERVENTION_REQUIRED</code>
                    <span class="text-slate-400">DISCOM legal/SDO block</span>
                </li>
            </ul>
        </div>
    </div>
</section>
