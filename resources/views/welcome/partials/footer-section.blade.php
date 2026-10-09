<!-- Footer -->
<footer class="border-t border-white/5 bg-slate-950 py-12 text-xs text-slate-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
        
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-brand-600/30 text-cyan-400 flex items-center justify-center font-bold">⚡</div>
            <div>
                <span class="font-bold text-slate-300 block">NBPDCL & BSPHCL Power Billing Automation Suite</span>
                <span class="text-[10px] text-slate-500">Enterprise SaaS Platform for Meter Readers & Billing Agencies</span>
            </div>
        </div>

        <div class="flex items-center gap-6 text-slate-400 font-medium">
            <a href="#comparison" class="hover:text-cyan-400 transition">Why Us</a>
            <a href="#features" class="hover:text-cyan-400 transition">Features</a>
            <a href="#interactive-demo" class="hover:text-cyan-400 transition">4-Box Demo</a>
            <a href="#roi-calculator" class="hover:text-cyan-400 transition">ROI Calculator</a>
            <a href="#pricing" class="hover:text-cyan-400 transition">Pricing</a>
            <a href="{{ route('login') }}" class="hover:text-cyan-400 transition">Sign In</a>
        </div>

        <div class="text-right">
            <div>&copy; {{ date('Y') }} NBPDCL Billing SaaS. All rights reserved.</div>
            <div class="text-[10px] text-slate-600 mt-0.5">256-Bit SSL Encryption • Secure Cloud Hosting</div>
        </div>

    </div>
</footer>
