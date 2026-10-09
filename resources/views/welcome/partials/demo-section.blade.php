<!-- 3. INTERACTIVE 4-BOX READING CALCULATOR SIMULATOR -->
<section id="interactive-demo" class="py-20 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-xs font-extrabold tracking-widest text-cyan-400 uppercase mb-3 block">Live Interactive Sandbox</span>
            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Try the 4-Box Meter Reading Engine</h2>
            <p class="text-slate-400 mt-4 text-sm font-medium">Test how our invariant-preserving algorithm calculates working meter readings in real-time.</p>
        </div>

        <div class="max-w-4xl mx-auto glass-panel p-6 sm:p-10 rounded-3xl border border-cyan-500/30 shadow-2xl relative">
            
            <!-- Interactive Controls Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 pb-8 mb-8 border-b border-white/10">
                <div>
                    <label class="text-xs font-bold text-slate-300 block mb-2">1. Previous Cycle Reading</label>
                    <input type="number" x-model.number="demoPrev" @input="recalculateDemo()" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono font-bold text-white focus:ring-cyan-500 focus:border-cyan-500" placeholder="500">
                    <span class="text-[10px] text-slate-400 mt-1 block">Previous month's working reading</span>
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-300 block mb-2">2. Billing Basis</label>
                    <select x-model="demoBasis" @change="recalculateDemo()" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-bold text-white focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="OK">OK — Normal Consumption (50 kWh)</option>
                        <option value="MD">MD — Defective Meter (76 kWh Avg)</option>
                        <option value="LK">LK — Locked Premise (35 kWh Avg)</option>
                        <option value="PL">PL — Power Limit Basis (60 kWh Avg)</option>
                    </select>
                    <span class="text-[10px] text-slate-400 mt-1 block">Extracted from PDF font</span>
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-300 block mb-2">3. Official PDF Current Reading</label>
                    <input type="number" x-model.number="demoPdf" @input="recalculateDemo()" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono font-bold text-white focus:ring-cyan-500 focus:border-cyan-500" placeholder="800">
                    <span class="text-[10px] text-slate-400 mt-1 block">Official billing server value</span>
                </div>
            </div>

            <!-- 4-Box Output Display -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Box 1: Working -->
                <div class="bg-blue-950/40 p-4 rounded-2xl border border-cyan-500/40 shadow-inner">
                    <span class="text-[10px] uppercase font-bold text-cyan-300">Box 1 • Working Reading</span>
                    <div class="text-2xl sm:text-3xl font-black text-white font-mono mt-1" x-text="demoWorking">550</div>
                    <span class="text-[10px] font-bold" :class="demoSyncColor" x-text="demoSyncLabel">⚡ Auto-Projected</span>
                </div>

                <!-- Box 2: Previous -->
                <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Box 2 • DB Previous</span>
                    <div class="text-2xl sm:text-3xl font-black text-slate-300 font-mono mt-1" x-text="demoPrev">500</div>
                    <span class="text-[10px] text-slate-500">Historical anchor</span>
                </div>

                <!-- Box 3: Smart Avg -->
                <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Box 3 • Smart Avg Units</span>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono mt-1"><span x-text="demoAvg">50</span> <span class="text-xs font-normal">kWh</span></div>
                    <span class="text-[10px] text-emerald-400">Median consumption</span>
                </div>

                <!-- Box 4: Official PDF -->
                <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Box 4 • Official PDF</span>
                    <div class="text-2xl sm:text-3xl font-black text-white font-mono mt-1" x-text="demoPdf">800</div>
                    <span class="text-[10px] text-slate-400">Server reading baseline</span>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-950 border border-white/5 flex items-center justify-between text-xs text-slate-400">
                <span class="flex items-center gap-2">
                    <span class="text-cyan-400 font-bold">🛡️ Invariant Formula:</span>
                    <code class="font-mono text-cyan-300">Working = Max(PDF_Reading, Previous_Reading + Avg_Units)</code>
                </span>
                <span class="text-[11px] font-bold text-emerald-400 hidden sm:inline">100% Inviolable Guarantee</span>
            </div>

        </div>

    </div>
</section>
