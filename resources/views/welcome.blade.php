<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NBPDCL Billing Automation Platform</title>

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Outfit', 'sans-serif'],
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
                        }
                    }
                }
            }
        </script>

        <style>
            body {
                background: radial-gradient(circle at 50% 0%, #0c2340 0%, #030712 100%);
            }
            .glass {
                background: rgba(255, 255, 255, 0.03);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.05);
            }
            .glass-hover:hover {
                background: rgba(255, 255, 255, 0.05);
                border-color: rgba(255, 255, 255, 0.1);
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body class="font-sans text-gray-200 min-h-screen flex flex-col justify-between selection:bg-brand-500 selection:text-white">
        
        <!-- Header / Navigation -->
        <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-cyan-400 flex items-center justify-center shadow-lg shadow-brand-900/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-gray-100 to-gray-400 bg-clip-text text-transparent">NBPDCL Billing SaaS</span>
            </div>

            @if (Route::has('login'))
                <nav class="flex items-center gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="glass px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-brand-600 hover:text-white hover:border-brand-600 transition-all duration-300 shadow-md">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-400 hover:text-white text-sm font-medium transition-colors duration-200">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-500 hover:to-brand-400 text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-lg shadow-brand-900/20 transition-all duration-300">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>

        <!-- Hero Section -->
        <main class="w-full max-w-6xl mx-auto px-6 py-12 md:py-24 flex flex-col items-center text-center z-10">
            <!-- Badge -->
            <div class="glass px-4 py-1.5 rounded-full text-xs font-semibold tracking-wider text-brand-300 uppercase mb-6 animate-pulse">
                ⚡ Automate Your Billing Workflow
            </div>

            <!-- Title -->
            <h1 class="text-4xl sm:text-6xl md:text-7xl font-extrabold tracking-tight text-white mb-6 max-w-4xl leading-tight">
                Bulk Electricity Bill Downloads <br class="hidden sm:inline" />
                <span class="bg-gradient-to-r from-brand-400 via-cyan-300 to-brand-500 bg-clip-text text-transparent">Made Instant & Simple</span>
            </h1>

            <!-- Description -->
            <p class="text-gray-400 text-lg md:text-xl max-w-2xl mb-10 leading-relaxed">
                Paste multiple CA numbers, download official PDFs in parallel, and automatically extract consumer details, meter readings, and billing periods in seconds.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 mb-20">
                <a href="{{ route('register') }}" class="bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-500 hover:to-brand-400 text-white text-base font-bold px-8 py-4 rounded-2xl shadow-xl shadow-brand-900/30 transition-all duration-300">
                    Get Started Free
                </a>
                <a href="{{ route('login') }}" class="glass text-white hover:bg-white/5 text-base font-semibold px-8 py-4 rounded-2xl transition-all duration-300">
                    Sign In
                </a>
            </div>

            <!-- Features Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full text-left">
                <!-- Feature 1 -->
                <div class="glass p-8 rounded-2xl transition-all duration-300 glass-hover">
                    <div class="w-12 h-12 rounded-xl bg-brand-950 border border-brand-900/30 flex items-center justify-center mb-6 text-brand-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Concurrent Downloader</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Download up to 100+ bills simultaneously in parallel. No more downloading PDF by PDF manually.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="glass p-8 rounded-2xl transition-all duration-300 glass-hover">
                    <div class="w-12 h-12 rounded-xl bg-brand-950 border border-brand-900/30 flex items-center justify-center mb-6 text-brand-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Smart PDF Data Extraction</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Automatically parses PDF text to extract Name, Amount, Readings, Units, and Meter Number with high accuracy.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="glass p-8 rounded-2xl transition-all duration-300 glass-hover">
                    <div class="w-12 h-12 rounded-xl bg-brand-950 border border-brand-900/30 flex items-center justify-center mb-6 text-brand-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Agent & User Data Isolation</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Securely scopes all consumer data and bills to your private account. Organized cleanly by year, month, and MRU code.
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-6 py-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500 z-10">
            <div>
                &copy; {{ date('Y') }} NBPDCL Billing SaaS. All rights reserved.
            </div>
            <div class="flex gap-4 mt-4 sm:mt-0">
                <span>Laravel v{{ Illuminate\Foundation\Application::VERSION }}</span>
                <span>PHP v{{ PHP_VERSION }}</span>
            </div>
        </footer>

    </body>
</html>
