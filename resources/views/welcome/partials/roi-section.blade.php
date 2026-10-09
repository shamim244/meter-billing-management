<!-- 5. INTERACTIVE ROI & TIME SAVINGS CALCULATOR -->
<section id="roi-calculator" class="py-20 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-xs font-extrabold tracking-widest text-cyan-400 uppercase mb-3 block">Calculate Your Agency's ROI</span>
            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">How Much Time & Money Will You Save?</h2>
            <p class="text-slate-400 mt-4 text-sm font-medium">Slide to your agency's scale and see the immediate monthly operational return.</p>
        </div>

        <div class="max-w-4xl mx-auto glass-panel p-8 sm:p-12 rounded-3xl border border-brand-500/30 shadow-2xl">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <!-- Sliders -->
                <div class="space-y-6">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-xs font-bold text-slate-300">Number of MRU Feeder Books</label>
                            <span class="text-sm font-black text-cyan-400 font-mono" x-text="mruCount + ' MRUs'"></span>
                        </div>
                        <input type="range" min="1" max="15" step="1" x-model.number="mruCount" class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-cyan-400">
                        <div class="flex justify-between text-[10px] text-slate-500 mt-1 font-mono">
                            <span>1 MRU</span>
                            <span>8 MRUs</span>
                            <span>15 MRUs</span>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-xs font-bold text-slate-300">Average Consumers per MRU</label>
                            <span class="text-sm font-black text-cyan-400 font-mono" x-text="consumersPerMru.toLocaleString() + ' Consumers'"></span>
                        </div>
                        <input type="range" min="500" max="4000" step="100" x-model.number="consumersPerMru" class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-cyan-400">
                        <div class="flex justify-between text-[10px] text-slate-500 mt-1 font-mono">
                            <span>500</span>
                            <span>2,000</span>
                            <span>4,000</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 text-xs text-slate-400 space-y-1">
                        <div class="flex justify-between">
                            <span>Total Monthly Consumer Bills:</span>
                            <span class="font-bold text-white font-mono" x-text="(mruCount * consumersPerMru).toLocaleString()"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Manual Processing Time:</span>
                            <span class="text-rose-400 font-bold font-mono" x-text="((mruCount * consumersPerMru * 8) / 3600).toFixed(1) + ' Hours'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>NBPDCL SaaS Automation Time:</span>
                            <span class="text-emerald-400 font-bold font-mono" x-text="((mruCount * consumersPerMru * 0.1) / 60).toFixed(1) + ' Minutes'"></span>
                        </div>
                    </div>
                </div>

                <!-- ROI Results Card -->
                <div class="glass-card p-6 sm:p-8 rounded-2xl border border-cyan-500/30 bg-gradient-to-tr from-slate-900 to-cyan-950/40 text-center space-y-6">
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-cyan-400 block mb-1">Estimated Hours Saved Monthly</span>
                        <div class="text-4xl sm:text-5xl font-black text-white font-mono tracking-tight" x-text="Math.round((mruCount * consumersPerMru * 7.9) / 3600) + ' hrs'"></div>
                        <span class="text-xs text-emerald-400 font-bold">~98% Faster Operations</span>
                    </div>

                    <div class="pt-4 border-t border-white/10">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400 block mb-1">Estimated Agency Labor Savings</span>
                        <div class="text-3xl sm:text-4xl font-black text-emerald-400 font-mono tracking-tight" x-text="'₹' + (Math.round((mruCount * consumersPerMru * 7.9) / 3600) * 350).toLocaleString()"></div>
                        <span class="text-[11px] text-slate-400">Based on ₹350/hr average operator cost</span>
                    </div>

                    <a href="{{ route('register') }}" class="w-full inline-flex items-center justify-center gap-2 py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-brand-600 hover:from-cyan-400 hover:to-brand-500 text-slate-950 font-black text-xs shadow-lg shadow-cyan-500/20 transition">
                        <span>Claim Your Efficiency Upgrade →</span>
                    </a>
                </div>
            </div>

        </div>

    </div>
</section>
