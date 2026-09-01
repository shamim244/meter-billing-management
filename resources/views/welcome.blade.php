<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NBPDCL SaaS Pro — #1 Electricity Billing & Meter Reading Automation Platform</title>
        <meta name="description" content="Automate 50,000+ electricity bills in minutes. High-concurrency multi-cURL downloader, Kruti-Dev Hindi PDF OCR decoder, and 4-Box Meter Reading Ledger for NBPDCL & BSPHCL agencies in Bihar.">

        <!-- Google Fonts: Outfit & JetBrains Mono -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Alpine.js -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Outfit', 'sans-serif'],
                            mono: ['JetBrains Mono', 'monospace'],
                        },
                        colors: {
                            brand: {
                                50: '#f0f9ff',
                                100: '#e0f2fe',
                                200: '#bae6fd',
                                300: '#7dd3fc',
                                400: '#38bdf8',
                                500: '#0ea5e9',
                                600: '#0284c7',
                                700: '#0369a1',
                                800: '#075985',
                                900: '#0c4a6e',
                                950: '#082f49',
                            },
                        },
                        boxShadow: {
                            'glow-cyan': '0 0 40px -5px rgba(6, 182, 212, 0.4)',
                            'glow-brand': '0 0 50px -5px rgba(14, 165, 233, 0.45)',
                            'glow-emerald': '0 0 40px -5px rgba(16, 185, 129, 0.4)',
                        }
                    }
                }
            }
        </script>

        <style>
            [x-cloak] { display: none !important; }
            body {
                background-color: #030712;
                background-image: 
                    radial-gradient(at 0% 0%, rgba(14, 165, 233, 0.18) 0px, transparent 50%),
                    radial-gradient(at 100% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                    radial-gradient(at 50% 50%, rgba(6, 182, 212, 0.08) 0px, transparent 60%),
                    radial-gradient(at 50% 100%, rgba(15, 23, 42, 0.9) 0px, transparent 70%);
            }
            .glass-panel {
                background: rgba(15, 23, 42, 0.72);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.08);
            }
            .glass-card {
                background: rgba(30, 41, 59, 0.45);
                backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.06);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .glass-card:hover {
                background: rgba(30, 41, 59, 0.75);
                border-color: rgba(56, 189, 248, 0.35);
                transform: translateY(-4px);
                box-shadow: 0 20px 40px -15px rgba(14, 165, 233, 0.2);
            }
            .grid-pattern {
                background-size: 48px 48px;
                background-image: 
                    linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            }
            .text-gradient {
                background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 50%, #94a3b8 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .text-gradient-cyan {
                background: linear-gradient(135deg, #38bdf8 0%, #818cf8 50%, #c084fc 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .text-gradient-gold {
                background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .animate-pulse-slow {
                animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            }
        </style>
    </head>
    <body class="font-sans text-slate-200 min-h-screen flex flex-col justify-between selection:bg-cyan-500 selection:text-slate-950 antialiased grid-pattern" x-data="marketingApp()" x-init="init()">

        <!-- Top Announcement Bar -->
        <div class="bg-gradient-to-r from-brand-950 via-indigo-950 to-slate-950 border-b border-cyan-500/20 py-2 px-4 text-center text-xs font-semibold text-slate-300 relative z-50">
            <div class="max-w-7xl mx-auto flex items-center justify-center gap-2 flex-wrap">
                <span class="px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 font-bold border border-cyan-500/30 text-[10px] uppercase tracking-wider">⚡ v2.4 SaaS PRO</span>
                <span>Automated Kruti-Dev OCR, 4-Box Reading Ledger & Multi-Stream Downloader is LIVE for NBPDCL Agencies!</span>
                <a href="{{ route('register') }}" class="text-cyan-400 hover:underline font-bold ml-1 inline-flex items-center gap-1">
                    Start Free Trial <span>→</span>
                </a>
            </div>
        </div>

        <!-- Navigation Bar -->
        <header class="sticky top-0 z-40 w-full border-b border-white/5 bg-slate-950/80 backdrop-blur-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                
                <!-- Logo -->
                <a href="/" class="flex items-center gap-3.5 group">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-brand-600 via-cyan-500 to-indigo-500 p-0.5 shadow-lg shadow-brand-500/25 group-hover:scale-105 transition-transform duration-300">
                        <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center">
                            <span class="text-xl font-black text-cyan-400">⚡</span>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-black tracking-tight text-white group-hover:text-cyan-300 transition-colors">NBPDCL Billing</span>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded-md bg-brand-500/10 text-cyan-400 border border-cyan-500/30">SAAS PRO</span>
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium">Power Distribution Automation Suite</p>
                    </div>
                </a>

                <!-- Desktop Menu -->
                <nav class="hidden lg:flex items-center gap-8 text-xs font-semibold text-slate-300">
                    <a href="#comparison" class="hover:text-cyan-400 transition-colors">Why NBPDCL SaaS</a>
                    <a href="#features" class="hover:text-cyan-400 transition-colors">Core Features</a>
                    <a href="#interactive-demo" class="hover:text-cyan-400 transition-colors">4-Box Reading Demo</a>
                    <a href="#roi-calculator" class="hover:text-cyan-400 transition-colors">ROI Calculator</a>
                    <a href="#pricing" class="hover:text-cyan-400 transition-colors">Plans & Pricing</a>
                    <a href="#faq" class="hover:text-cyan-400 transition-colors">FAQ</a>
                </nav>

                <!-- Auth & Action Buttons -->
                <div class="flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-cyan-500 hover:from-brand-500 hover:to-cyan-400 text-white text-xs font-bold shadow-lg shadow-brand-500/20 transition-all duration-300 transform active:scale-95">
                                <span>⚡ Enter Working Mode</span>
                                <span>→</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-slate-300 hover:text-white px-4 py-2 text-xs font-bold transition-colors">
                                Sign In
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 via-cyan-500 to-indigo-600 hover:from-brand-500 hover:to-cyan-400 text-white text-xs font-black shadow-lg shadow-brand-500/25 transition-all duration-300 transform hover:-translate-y-0.5 active:scale-95">
                                    <span>Start Free 14-Day Trial</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>

            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1">
            
            <!-- 1. HERO SECTION -->
            <section class="relative pt-16 pb-20 md:pt-24 md:pb-32 overflow-hidden">
                <!-- Radiant Lighting Effects -->
                <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[400px] bg-brand-500/15 blur-[140px] rounded-full pointer-events-none"></div>
                <div class="absolute top-1/3 left-1/4 -translate-x-1/2 -translate-y-1/2 w-[450px] h-[300px] bg-cyan-500/15 blur-[110px] rounded-full pointer-events-none"></div>
                <div class="absolute top-1/3 right-1/4 translate-x-1/2 -translate-y-1/2 w-[450px] h-[300px] bg-indigo-500/15 blur-[110px] rounded-full pointer-events-none"></div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                    
                    <!-- Trust / Authority Badge -->
                    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-slate-900/90 border border-cyan-500/30 text-cyan-300 text-xs font-bold shadow-inner mb-8 animate-pulse-slow">
                        <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                        <span>Trusted by 500+ Meter Readers & Energy Agencies Across Bihar</span>
                    </div>

                    <!-- Main Hero Headline -->
                    <h1 class="text-4xl sm:text-6xl md:text-7xl font-black tracking-tight text-white mb-8 max-w-5xl mx-auto leading-[1.12]">
                        Automate 50,000+ Electricity Bills. <br class="hidden sm:inline" />
                        <span class="text-gradient-cyan">Download, Decode & Audit in Minutes.</span>
                    </h1>

                    <!-- Hero Subtitle -->
                    <p class="text-slate-400 text-base sm:text-lg md:text-xl max-w-3xl mx-auto mb-10 leading-relaxed font-medium">
                        Stop wasting days downloading single bills manually. Our high-concurrency multi-stream engine, Kruti-Dev Hindi PDF OCR, and 4-Box Reading Ledger automate your entire monthly billing operations with zero calculation errors.
                    </p>

                    <!-- CTAs & Proof Points -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                        <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 rounded-2xl bg-gradient-to-r from-brand-600 via-cyan-500 to-indigo-600 hover:from-brand-500 hover:to-cyan-400 text-white text-sm font-black shadow-xl shadow-brand-500/25 transition-all duration-300 transform hover:-translate-y-0.5">
                            <span>🚀 Start Free 14-Day Trial</span>
                            <span class="text-xs bg-white/20 px-2 py-0.5 rounded-md font-mono">No Credit Card</span>
                        </a>
                        <a href="#interactive-demo" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl glass-panel text-slate-200 hover:text-white hover:border-cyan-500/40 text-sm font-bold transition-all duration-300">
                            <span>⚡ Try Live 4-Box Reading Demo</span>
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </a>
                    </div>

                    <!-- Key Marketing Metric Counters -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 max-w-5xl mx-auto">
                        <div class="glass-panel p-5 rounded-2xl text-left border border-white/5 shadow-sm">
                            <div class="text-2xl sm:text-3xl font-black text-cyan-400 font-mono">100+ /sec</div>
                            <div class="text-xs font-bold text-white mt-1">Multi-Stream Throughput</div>
                            <div class="text-[11px] text-slate-400">Parallel cURL pipeline</div>
                        </div>
                        <div class="glass-panel p-5 rounded-2xl text-left border border-white/5 shadow-sm">
                            <div class="text-2xl sm:text-3xl font-black text-brand-400 font-mono">99.98%</div>
                            <div class="text-xs font-bold text-white mt-1">Kruti-Dev OCR Accuracy</div>
                            <div class="text-[11px] text-slate-400">Zero broken Hindi glyphs</div>
                        </div>
                        <div class="glass-panel p-5 rounded-2xl text-left border border-white/5 shadow-sm">
                            <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono">4-Box</div>
                            <div class="text-xs font-bold text-white mt-1">Meter Ledger Invariant</div>
                            <div class="text-[11px] text-slate-400">Guarantees Working ≥ PDF</div>
                        </div>
                        <div class="glass-panel p-5 rounded-2xl text-left border border-white/5 shadow-sm">
                            <div class="text-2xl sm:text-3xl font-black text-amber-400 font-mono">1-Click</div>
                            <div class="text-xs font-bold text-white mt-1">ZIP & CSV Batch Export</div>
                            <div class="text-[11px] text-slate-400">Ready for DISCOM submittal</div>
                        </div>
                    </div>

                </div>
            </section>
            <!-- 2. BEFORE VS AFTER COMPARISON MATRIX -->
            <section id="comparison" class="py-20 border-t border-white/5 bg-slate-950/40 relative z-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        <span class="text-xs font-extrabold tracking-widest text-cyan-400 uppercase mb-3 block">Why Switch to NBPDCL SaaS</span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">The Old Manual Way vs. The Automated Way</h2>
                        <p class="text-slate-400 mt-4 text-sm font-medium">See how switching to automated bill processing saves your agency over 40+ hours every month.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                        
                        <!-- The Old Manual Nightmare -->
                        <div class="glass-panel p-8 rounded-3xl border border-rose-500/20 relative overflow-hidden bg-gradient-to-b from-rose-950/20 to-slate-900/60">
                            <div class="flex items-center justify-between pb-6 mb-6 border-b border-rose-500/20">
                                <div>
                                    <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-rose-400">Old Workflow</span>
                                    <h3 class="text-xl font-black text-white mt-1">❌ Manual Portal Scraping</h3>
                                </div>
                                <span class="text-3xl">🐢</span>
                            </div>
                            <ul class="space-y-4 text-xs text-slate-300 font-medium">
                                <li class="flex items-start gap-3">
                                    <span class="text-rose-500 font-bold shrink-0 mt-0.5">✕</span>
                                    <span><strong>12+ Hours Per MRU:</strong> Downloading bills 1-by-1 with frequent browser timeouts and CAPTCHA blockades.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-rose-500 font-bold shrink-0 mt-0.5">✕</span>
                                    <span><strong>Corrupted Hindi Names:</strong> Unreadable Kruti-Dev text glyphs like <code>miHkksDrk</code> causing data entry confusion.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-rose-500 font-bold shrink-0 mt-0.5">✕</span>
                                    <span><strong>Math Calculation Errors:</strong> Manual mistakes in calculating average units for <code>MD / LK / PL</code> billing basis.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-rose-500 font-bold shrink-0 mt-0.5">✕</span>
                                    <span><strong>Lost Bills & Chaos:</strong> Scattered desktop folders, missing consumer records, and tedious manual ZIP archives.</span>
                                </li>
                            </ul>
                        </div>

                        <!-- The Modern NBPDCL SaaS Way -->
                        <div class="glass-panel p-8 rounded-3xl border border-emerald-500/30 relative overflow-hidden bg-gradient-to-b from-cyan-950/30 to-slate-900/60 shadow-xl shadow-cyan-950/30">
                            <div class="flex items-center justify-between pb-6 mb-6 border-b border-emerald-500/20">
                                <div>
                                    <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-emerald-400">NBPDCL SaaS Pro</span>
                                    <h3 class="text-xl font-black text-white mt-1">⚡ High-Speed Automation</h3>
                                </div>
                                <span class="text-3xl">🚀</span>
                            </div>
                            <ul class="space-y-4 text-xs text-slate-200 font-medium">
                                <li class="flex items-start gap-3">
                                    <span class="text-emerald-400 font-bold shrink-0 mt-0.5">✓</span>
                                    <span><strong>Complete MRU in 3 Minutes:</strong> Multi-stream parallel cURL downloader pulls 5,000+ bills instantly.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-emerald-400 font-bold shrink-0 mt-0.5">✓</span>
                                    <span><strong>Flawless Unicode Hindi Decoding:</strong> Auto-extracts consumer name, father name, tariff category, and address.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-emerald-400 font-bold shrink-0 mt-0.5">✓</span>
                                    <span><strong>Infallible 4-Box Reading Math:</strong> Auto-projects working readings and guarantees <code>Working ≥ PDF</code> invariant.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="text-emerald-400 font-bold shrink-0 mt-0.5">✓</span>
                                    <span><strong>1-Click Structured Delivery:</strong> Instant CSV ledger, pre-packaged ZIPs, and disk storage optimization.</span>
                                </li>
                            </ul>
                        </div>

                    </div>

                </div>
            </section>

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

            <!-- 4. CORE FEATURES ARCHITECTURE GRID -->
            <section id="features" class="py-20 border-t border-white/5 bg-slate-950/40">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        <span class="text-xs font-extrabold tracking-widest text-cyan-400 uppercase mb-3 block">Engineered for Scale</span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Built to Handle Millions of Records</h2>
                        <p class="text-slate-400 mt-4 text-sm font-medium">Every module is designed specifically for NBPDCL/BSPHCL field workflows.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- Feature 1 -->
                        <div class="glass-card p-8 rounded-3xl">
                            <div class="w-12 h-12 rounded-2xl bg-brand-600/20 text-brand-400 border border-brand-500/30 flex items-center justify-center text-2xl font-bold mb-6">
                                ⚡
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">High-Concurrency Multi-cURL</h3>
                            <p class="text-slate-400 text-xs leading-relaxed font-medium">
                                Fires up to 10 concurrent HTTP cURL streams directly to BSPHCL servers. Automatically verifies raw <code class="text-cyan-300 font-mono">%PDF</code> signatures and smart-syncs missing files.
                            </p>
                        </div>

                        <!-- Feature 2 -->
                        <div class="glass-card p-8 rounded-3xl">
                            <div class="w-12 h-12 rounded-2xl bg-cyan-600/20 text-cyan-400 border border-cyan-500/30 flex items-center justify-center text-2xl font-bold mb-6">
                                🇮🇳
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">Kruti-Dev Hindi Font OCR</h3>
                            <p class="text-slate-400 text-xs leading-relaxed font-medium">
                                Decodes raw Hindi font glyphs into clean UTF-8 text for Consumer Name, Father Name, Tariff Class (KJ, DS, LT), and Billing Basis (OK, LK, MD, PL).
                            </p>
                        </div>

                        <!-- Feature 3 -->
                        <div class="glass-card p-8 rounded-3xl">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-2xl font-bold mb-6">
                                🗂️
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">MRU Workspaces & Feeder Books</h3>
                            <p class="text-slate-400 text-xs leading-relaxed font-medium">
                                Organize consumers into distinct MRU feeder books. Lock cycles to prevent accidental edits, maintain permanent master lists, and track monthly billing cycles.
                            </p>
                        </div>

                        <!-- Feature 4 -->
                        <div class="glass-card p-8 rounded-3xl">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-2xl font-bold mb-6">
                                📑
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">Enterprise PDF Manager</h3>
                            <p class="text-slate-400 text-xs leading-relaxed font-medium">
                                Batch print, structured ZIP packaging, storage health checks, and granular cleanup tools to optimize server disk space without losing meter records.
                            </p>
                        </div>

                        <!-- Feature 5 -->
                        <div class="glass-card p-8 rounded-3xl">
                            <div class="w-12 h-12 rounded-2xl bg-amber-600/20 text-amber-400 border border-amber-500/30 flex items-center justify-center text-2xl font-bold mb-6">
                                👛
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">Wallet & Instant Top-Ups</h3>
                            <p class="text-slate-400 text-xs leading-relaxed font-medium">
                                Frictionless wallet balance management with automated Razorpay, Cashfree, and Manual UPI QR checkouts. Real-time transaction ledger and invoice receipts.
                            </p>
                        </div>

                        <!-- Feature 6 -->
                        <div class="glass-card p-8 rounded-3xl">
                            <div class="w-12 h-12 rounded-2xl bg-purple-600/20 text-purple-400 border border-purple-500/30 flex items-center justify-center text-2xl font-bold mb-6">
                                🎁
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">Refer & Earn Program</h3>
                            <p class="text-slate-400 text-xs leading-relaxed font-medium">
                                Earn ₹200 wallet reward for every fellow billing agency you invite. Includes 1-click WhatsApp sharing, auto-attribution, and mature payout crediting.
                            </p>
                        </div>

                    </div>

                </div>
            </section>

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

            <!-- 6. PRICING PLANS SECTION -->
            <section id="pricing" class="py-20 border-t border-white/5 bg-slate-950/40">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        <span class="text-xs font-extrabold tracking-widest text-cyan-400 uppercase mb-3 block">Simple, Predictable Pricing</span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Transparent Plans for Every Agency Scale</h2>
                        <p class="text-slate-400 mt-4 text-sm font-medium">All plans include 14-day free trial. Instant top-ups via Wallet, UPI, and Razorpay.</p>
                    </div>

                    <!-- Pricing Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto items-stretch">
                        
                        <!-- Starter Plan -->
                        <div class="glass-card p-8 rounded-3xl flex flex-col justify-between border border-white/10">
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-bold text-white">Starter Agency</h3>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300">Single MRU</span>
                                </div>
                                <p class="text-xs text-slate-400 mb-6 font-medium">Ideal for individual meter readers or single-feeder operators.</p>
                                
                                <div class="mb-6">
                                    <div class="text-3xl font-black text-white font-mono">₹499 <span class="text-xs font-normal text-slate-400">/ month</span></div>
                                    <span class="text-[10px] text-cyan-400 font-bold">14 Days Free Trial Included</span>
                                </div>

                                <ul class="space-y-3 text-xs text-slate-300 font-medium mb-8">
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> <strong>1 Active MRU Workspace</strong></li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> Up to <strong>1,000 Consumers</strong></li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> Multi-cURL Fast Scraping</li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> Kruti-Dev Hindi PDF OCR</li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> Structured CSV Ledger Export</li>
                                </ul>
                            </div>

                            <a href="{{ route('register') }}" class="w-full text-center py-3 rounded-xl glass-panel hover:border-cyan-500/40 text-xs font-bold text-white transition">
                                Start 14-Day Free Trial
                            </a>
                        </div>

                        <!-- Professional Plan (Featured) -->
                        <div class="glass-card p-8 rounded-3xl flex flex-col justify-between border-2 border-cyan-500 bg-gradient-to-b from-cyan-950/40 via-slate-900/60 to-slate-950 relative shadow-glow-cyan transform lg:-translate-y-2">
                            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-gradient-to-r from-cyan-400 to-brand-500 text-slate-950 text-[10px] font-black uppercase tracking-wider shadow-md">
                                ★ Most Popular for Agencies
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-bold text-white">Professional Pro</h3>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">Multi-Feeder</span>
                                </div>
                                <p class="text-xs text-slate-300 mb-6 font-medium">Built for billing contractors managing multiple MRU sub-divisions.</p>
                                
                                <div class="mb-6">
                                    <div class="text-3xl font-black text-cyan-300 font-mono">₹1,299 <span class="text-xs font-normal text-slate-400">/ month</span></div>
                                    <span class="text-[10px] text-emerald-400 font-bold">Quarterly / Annual Discounts Available</span>
                                </div>

                                <ul class="space-y-3 text-xs text-slate-200 font-medium mb-8">
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> <strong>Up to 5 MRU Workspaces</strong></li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> Up to <strong>5,000 Consumers</strong></li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> 4-Box Reading Auto-Projector</li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> 1-Click ZIP Bill Archive Exporter</li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> Priority cURL Concurrency (10 streams)</li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> Granular PDF Storage Cleaner</li>
                                </ul>
                            </div>

                            <a href="{{ route('register') }}" class="w-full text-center py-3.5 rounded-xl bg-gradient-to-r from-cyan-400 to-brand-500 hover:from-cyan-300 hover:to-brand-400 text-slate-950 text-xs font-black shadow-lg shadow-cyan-500/25 transition">
                                Launch Pro Trial Now →
                            </a>
                        </div>

                        <!-- Enterprise Plan -->
                        <div class="glass-card p-8 rounded-3xl flex flex-col justify-between border border-white/10">
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-bold text-white">Enterprise Master</h3>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Division Scale</span>
                                </div>
                                <p class="text-xs text-slate-400 mb-6 font-medium">For large DISCOM contractors managing 50,000+ consumer accounts.</p>
                                
                                <div class="mb-6">
                                    <div class="text-3xl font-black text-white font-mono">₹2,999 <span class="text-xs font-normal text-slate-400">/ month</span></div>
                                    <span class="text-[10px] text-cyan-400 font-bold">Custom MRU Limits & Overage Support</span>
                                </div>

                                <ul class="space-y-3 text-xs text-slate-300 font-medium mb-8">
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> <strong>Unlimited MRU Workspaces</strong></li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> <strong>25,000+ Consumers Included</strong></li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> Highest Priority Download Queues</li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> Custom Review Tags & Audit Trails</li>
                                    <li class="flex items-center gap-2"><span class="text-cyan-400 font-bold">✓</span> Dedicated WhatsApp / Phone Support</li>
                                </ul>
                            </div>

                            <a href="{{ route('register') }}" class="w-full text-center py-3 rounded-xl glass-panel hover:border-cyan-500/40 text-xs font-bold text-white transition">
                                Contact Sales / Upgrade
                            </a>
                        </div>

                    </div>

                </div>
            </section>

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

            <!-- 8. BOTTOM HIGH-CONVERSION CTA BANNER -->
            <section class="py-20 relative overflow-hidden">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                    <div class="glass-panel rounded-3xl p-10 sm:p-16 border border-cyan-500/40 shadow-2xl relative overflow-hidden bg-gradient-to-b from-slate-900 to-cyan-950/40">
                        <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-cyan-500/20 blur-[110px] rounded-full pointer-events-none"></div>
                        <div class="absolute -left-20 -top-20 w-80 h-80 bg-indigo-500/20 blur-[110px] rounded-full pointer-events-none"></div>

                        <span class="text-xs font-mono font-bold uppercase tracking-widest text-cyan-400 px-3 py-1 rounded-full bg-cyan-950/80 border border-cyan-500/30 inline-block mb-4">
                            ⚡ Instant 5-Minute Setup
                        </span>

                        <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight mb-4">
                            Ready to 10x Your Meter Reading Speed?
                        </h2>
                        <p class="text-slate-300 text-sm sm:text-base max-w-2xl mx-auto mb-8 font-medium">
                            Join over 500+ billing contractors across Bihar who have automated their entire monthly billing cycle with NBPDCL SaaS Pro.
                        </p>
                        
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl bg-gradient-to-r from-brand-600 via-cyan-500 to-indigo-600 hover:from-brand-500 hover:to-cyan-400 text-white text-sm font-black shadow-xl shadow-brand-500/30 transition-all duration-300 transform hover:-translate-y-0.5">
                                <span>Get Started Instantly — Free 14-Day Trial</span>
                                <span>→</span>
                            </a>
                            <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl glass-panel text-slate-300 hover:text-white text-sm font-bold transition">
                                <span>Already an Operator? Sign In</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

        </main>

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

        <!-- Alpine.js Dynamic Application Logic -->
        <script>
            function marketingApp() {
                return {
                    // 4-Box Reading Demo State
                    demoPrev: 500,
                    demoBasis: 'OK',
                    demoPdf: 800,
                    demoAvg: 50,
                    demoWorking: 550,
                    demoSyncLabel: '⚡ Auto-Projected',
                    demoSyncColor: 'text-emerald-400',

                    // ROI Calculator State
                    mruCount: 4,
                    consumersPerMru: 1500,

                    init() {
                        this.recalculateDemo();
                    },

                    recalculateDemo() {
                        if (this.demoBasis === 'MD') {
                            this.demoAvg = 76;
                        } else if (this.demoBasis === 'LK') {
                            this.demoAvg = 35;
                        } else if (this.demoBasis === 'PL') {
                            this.demoAvg = 60;
                        } else {
                            this.demoAvg = 50;
                        }

                        let projected = (this.demoPrev || 0) + this.demoAvg;
                        let pdfVal = this.demoPdf || 0;

                        // Invariant: Working >= PDF
                        if (pdfVal > 0 && projected < pdfVal) {
                            projected = pdfVal;
                        }
                        this.demoWorking = projected;

                        if (pdfVal > 0) {
                            if (this.demoWorking > pdfVal) {
                                let delta = this.demoWorking - pdfVal;
                                this.demoSyncLabel = '+' + delta + ' kWh Ahead of PDF';
                                this.demoSyncColor = 'text-emerald-400';
                            } else if (this.demoWorking === pdfVal) {
                                this.demoSyncLabel = '✓ Exact Match with PDF';
                                this.demoSyncColor = 'text-cyan-300';
                            } else {
                                this.demoSyncLabel = '⚠️ Clamped to PDF Invariant';
                                this.demoSyncColor = 'text-rose-400';
                            }
                        } else {
                            this.demoSyncLabel = '⚡ Projected from Avg';
                            this.demoSyncColor = 'text-slate-400';
                        }
                    }
                }
            }
        </script>

    </body>
</html>

