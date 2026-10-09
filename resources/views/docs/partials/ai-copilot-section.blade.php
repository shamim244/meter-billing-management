<!-- AI AGENT & COPILOT SETUP SECTION -->
<section id="ai-agent-guide" class="space-y-6 pt-6">
    <div class="border-b border-purple-900/50 pb-4">
        <h2 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
            <span>🤖</span>
            <span>AI Agent & Copilot Tool-Calling Setup</span>
        </h2>
        <p class="text-xs text-purple-300 mt-1">Connect Claude, ChatGPT (Custom GPTs / Actions), Cursor, or LangChain directly to this API without writing custom backend code.</p>
    </div>

    <div class="glass-panel p-6 sm:p-8 rounded-3xl space-y-6 border border-purple-500/20">
        <div>
            <h3 class="text-sm font-bold text-white mb-1">1. OpenAPI 3.0 Machine Specification</h3>
            <p class="text-xs text-slate-400 mb-3">Copy this URL directly into ChatGPT Custom Actions or Claude Tool Use:</p>
            <div class="p-3 rounded-xl bg-black/60 border border-purple-500/30 flex items-center justify-between font-mono text-xs text-purple-300">
                <span>{{ $openapiUrl }}</span>
                <button @click="copyCode('{{ $openapiUrl }}')" class="text-slate-400 hover:text-white font-sans font-bold">Copy URL</button>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-white">2. AI Copilot System Prompt / Instructions</h3>
                <button @click="copyCode($refs.promptBox.innerText)" class="text-xs font-bold text-purple-400 hover:text-purple-300">📋 Copy Prompt</button>
            </div>
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 font-mono text-xs text-slate-300 overflow-x-auto max-h-80">
                <pre x-ref="promptBox" class="whitespace-pre-wrap">You are an intelligent NBPDCL Electricity Billing Copilot.
You have access to the NBPDCL REST API to assist meter readers and billing agencies in Bihar.

Your capabilities:
1. Query active MRU workspaces and billing cycles using GET /bills.
2. Filter accounts by pending, doubt, critical, or submitted.
3. Review consumer meter readings and update statuses using PATCH /bills/review.
4. Record structured reasons:
   - For Doubt: PREMISES_LOCKED, SUSPICIOUS_READING, SUSPICIOUS_AMOUNT, PREV_MONTH_MISMATCH, OWNER_RECHECK_REQUEST.
   - For Critical: METER_BURNT_DEAD, METER_TAMPERED_BYPASS, METER_MISSING_STOLEN, PREMISES_DEMOLISHED.

Always confirm consumer name, CA number, and previous reading before proposing status submissions.</pre>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-purple-950/20 border border-purple-800/40 text-xs text-purple-200 space-y-2">
            <span class="font-bold block">💡 Example AI Prompt Seeds to Test With Your Agent:</span>
            <ul class="list-disc list-inside space-y-1 text-slate-300">
                <li><em>"Show me all pending bills in MRU 0244 for April 2026 sorted by highest arrears."</em></li>
                <li><em>"Consumer 10230063090 had a locked gate today. Mark them as Doubt with reason PREMISES_LOCKED."</em></li>
                <li><em>"Give me a summary of how many meters were marked as METER_BURNT_DEAD this month."</em></li>
            </ul>
        </div>
    </div>
</section>
