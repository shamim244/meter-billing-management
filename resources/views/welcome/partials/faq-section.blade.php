<!-- 7. FREQUENTLY ASKED QUESTIONS (FAQ) -->
<section id="faq" class="py-20 relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-16">
            <span class="text-xs font-extrabold tracking-widest text-cyan-400 uppercase mb-3 block">Got Questions?</span>
            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Frequently Asked Questions</h2>
        </div>

        <div class="space-y-4" x-data="{ activeFaq: null }">
            
            <div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
                <button @click="activeFaq = (activeFaq === 1 ? null : 1)" class="w-full p-5 text-left text-sm font-bold text-white flex items-center justify-between transition">
                    <span>Does this work with all NBPDCL / BSPHCL divisions in Bihar?</span>
                    <span class="text-cyan-400 font-mono" x-text="activeFaq === 1 ? '−' : '+'">+</span>
                </button>
                <div x-show="activeFaq === 1" x-cloak class="px-5 pb-5 text-xs text-slate-400 leading-relaxed border-t border-white/5 pt-3">
                    Yes. The platform works across all 38 districts and supply divisions under North Bihar Power Distribution Company Ltd (NBPDCL) and Bihar State Power Holding Company Ltd (BSPHCL).
                </div>
            </div>

            <div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
                <button @click="activeFaq = (activeFaq === 2 ? null : 2)" class="w-full p-5 text-left text-sm font-bold text-white flex items-center justify-between transition">
                    <span>How does Kruti-Dev Hindi OCR font decoding work?</span>
                    <span class="text-cyan-400 font-mono" x-text="activeFaq === 2 ? '−' : '+'">+</span>
                </button>
                <div x-show="activeFaq === 2" x-cloak class="px-5 pb-5 text-xs text-slate-400 leading-relaxed border-t border-white/5 pt-3">
                    Official BSPHCL bills encode Hindi names using legacy Kruti-Dev font byte glyphs. Our built-in OCR translation dictionary automatically converts those raw character codes into clean Unicode Hindi/English text without requiring any special fonts installed on your computer.
                </div>
            </div>

            <div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
                <button @click="activeFaq = (activeFaq === 3 ? null : 3)" class="w-full p-5 text-left text-sm font-bold text-white flex items-center justify-between transition">
                    <span>What is the 4-Box Reading invariant rule?</span>
                    <span class="text-cyan-400 font-mono" x-text="activeFaq === 3 ? '−' : '+'">+</span>
                </button>
                <div x-show="activeFaq === 3" x-cloak class="px-5 pb-5 text-xs text-slate-400 leading-relaxed border-t border-white/5 pt-3">
                    The 4-Box system connects Box 1 (Working Reading), Box 2 (DB Previous Reading), Box 3 (Smart Average Units), and Box 4 (Official PDF Reading). It guarantees that a meter reader cannot accidentally enter a working reading lower than the official server reading, eliminating DISCOM penalty risks.
                </div>
            </div>

            <div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
                <button @click="activeFaq = (activeFaq === 4 ? null : 4)" class="w-full p-5 text-left text-sm font-bold text-white flex items-center justify-between transition">
                    <span>How does payment & wallet top-up work?</span>
                    <span class="text-cyan-400 font-mono" x-text="activeFaq === 4 ? '−' : '+'">+</span>
                </button>
                <div x-show="activeFaq === 4" x-cloak class="px-5 pb-5 text-xs text-slate-400 leading-relaxed border-t border-white/5 pt-3">
                    You can top up your agency wallet directly via instant UPI, Google Pay, PhonePe, Paytm, QR code, Net Banking, or Credit/Debit Card. Subscription renewals and quota overages are automatically and transparently debited from your wallet.
                </div>
            </div>

        </div>

    </div>
</section>
