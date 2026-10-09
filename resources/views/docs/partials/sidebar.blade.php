<!-- Sticky Left Sidebar -->
<aside class="lg:col-span-3 space-y-6">
    <div class="sticky top-24 space-y-5">
        <!-- Search Box -->
        <div class="relative">
            <input type="text" 
                   x-model="searchQuery" 
                   placeholder="Filter endpoints (e.g. review, queue)..." 
                   class="w-full text-xs rounded-xl border-slate-800 bg-slate-900 text-slate-200 placeholder-slate-500 focus:ring-brand-500 focus:border-brand-500 p-2.5 pl-8 font-medium">
            <span class="absolute left-2.5 top-3 text-slate-500 text-xs">🔍</span>
        </div>

        <!-- Navigation List -->
        <nav class="space-y-4 text-xs font-semibold">
            <div class="space-y-1">
                <div class="text-[10px] font-black uppercase text-slate-500 tracking-wider px-3">
                    Getting Started
                </div>
                <a href="#quickstart" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                    ⚡ Quickstart & Auth
                </a>
                <a href="#endpoints" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                    📋 Endpoints Catalog
                </a>
                <a href="#reason-codes" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                    🏷️ Doubt & Critical Codes
                </a>
            </div>

            <div class="space-y-1">
                <div class="text-[10px] font-black uppercase text-slate-500 tracking-wider px-3">
                    Core Automation APIs
                </div>
                <a href="#endpoint-bills" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                    <span class="text-[10px] font-bold text-sky-400 font-mono">GET</span> /bills
                </a>
                <a href="#endpoint-review" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                    <span class="text-[10px] font-bold text-amber-400 font-mono">PATCH</span> /bills/review
                </a>
                <a href="#endpoint-batch-sync" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                    <span class="text-[10px] font-bold text-emerald-400 font-mono">POST</span> /bills/batch-sync
                </a>
                <a href="#endpoint-queue" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                    <span class="text-[10px] font-bold text-sky-400 font-mono">GET</span> /automation/queue
                </a>
                <a href="#endpoint-mrus" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                    <span class="text-[10px] font-bold text-sky-400 font-mono">GET</span> /mrus
                </a>
            </div>

            <div class="space-y-1">
                <div class="text-[10px] font-black uppercase text-slate-500 tracking-wider px-3">
                    Interactive & AI
                </div>
                <a href="#try-it-out" class="block px-3 py-1.5 rounded-lg text-cyan-400 font-bold hover:bg-slate-900 transition flex items-center justify-between">
                    <span>🎮 Live API Console</span>
                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                </a>
                <a href="#ai-agent-guide" class="block px-3 py-1.5 rounded-lg text-purple-400 font-bold hover:bg-slate-900 transition">
                    🤖 AI Agent / Copilot Setup
                </a>
            </div>
        </nav>
    </div>
</aside>
