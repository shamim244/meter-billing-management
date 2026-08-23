<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NBPDCL SaaS') }} - Portal</title>

        <!-- Google Fonts: Outfit & Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Tailwind & Alpine CDNs -->
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
                                50: '#f0f7ff',
                                100: '#e0effe',
                                200: '#bae2fd',
                                300: '#7cc8fc',
                                400: '#38abfa',
                                500: '#0e91eb',
                                600: '#0273c7',
                                700: '#035ca1',
                                800: '#074e85',
                                900: '#0c426e',
                                950: '#082a49',
                            },
                        },
                        animation: {
                            'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                            'blob': 'blob 7s infinite',
                        },
                        keyframes: {
                            blob: {
                                '0%': { transform: 'translate(0px, 0px) scale(1)' },
                                '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                                '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                                '100%': { transform: 'translate(0px, 0px) scale(1)' },
                            }
                        }
                    }
                }
            }
        </script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <style>
            [x-cloak] { display: none !important; }
            body {
                background-color: #030712;
                background-image: 
                    radial-gradient(at 0% 0%, rgba(14, 145, 235, 0.15) 0px, transparent 50%),
                    radial-gradient(at 100% 100%, rgba(56, 189, 248, 0.1) 0px, transparent 50%),
                    radial-gradient(at 50% 50%, rgba(15, 23, 42, 0.8) 0px, transparent 100%);
            }
            .glass-panel {
                background: rgba(15, 23, 42, 0.75);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.08);
            }
            .glass-input {
                background: rgba(3, 7, 18, 0.6);
                border: 1px solid rgba(255, 255, 255, 0.12);
                transition: all 0.2s ease;
            }
            .glass-input:focus {
                background: rgba(3, 7, 18, 0.85);
                border-color: #38abfa;
                box-shadow: 0 0 0 3px rgba(56, 171, 250, 0.2);
            }
        </style>
    </head>
    <body class="font-sans text-slate-100 antialiased min-h-screen flex flex-col justify-between selection:bg-brand-500 selection:text-white relative overflow-x-hidden">
        
        <!-- Ambient Decorative Glowing Orbs -->
        <div class="fixed top-0 left-1/4 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none animate-blob"></div>
        <div class="fixed bottom-0 right-1/4 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none animate-blob animation-delay-2000"></div>

        <!-- Top Header Navigation -->
        <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between relative z-10">
            <a href="/" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-cyan-400 p-0.5 shadow-lg shadow-brand-500/20 group-hover:scale-105 transition duration-200">
                    <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                        <span class="text-xl">⚡</span>
                    </div>
                </div>
                <div>
                    <div class="text-lg font-black tracking-tight text-white flex items-center gap-2">
                        <span>NBPDCL</span>
                        <span class="text-xs font-extrabold uppercase px-1.5 py-0.5 rounded bg-brand-500/20 text-brand-300 border border-brand-500/30">SaaS Pro</span>
                    </div>
                    <p class="text-[11px] text-slate-400 font-medium">Power Billing & Ledger Automation</p>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>System Online</span>
                </div>
                <a href="/" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800/60 hover:bg-slate-800 border border-slate-700/60 transition">
                    ← Home
                </a>
            </div>
        </header>

        <!-- Main Content Slot -->
        <main class="w-full flex-1 flex flex-col justify-center items-center px-4 py-8 relative z-10">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-3 relative z-10 border-t border-slate-800/40">
            <div class="flex items-center gap-2">
                <span>© {{ date('Y') }} NBPDCL / BSPHCL Power Billing Ecosystem</span>
            </div>
            <div class="flex items-center gap-4 text-[11px]">
                <span class="inline-flex items-center gap-1 text-slate-400">
                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    256-Bit SSL Encrypted
                </span>
                <span class="text-slate-600">•</span>
                <span class="font-mono text-slate-400">v2.4 Pro</span>
            </div>
        </footer>

    </body>
</html>
