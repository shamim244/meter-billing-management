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

        <!-- Modularized Landing Page Styles -->
        <link rel="stylesheet" href="{{ asset('css/welcome/welcome.css') }}?v={{ file_exists(public_path('css/welcome/welcome.css')) ? filemtime(public_path('css/welcome/welcome.css')) : time() }}">
    </head>
    <body class="font-sans text-slate-200 min-h-screen flex flex-col justify-between selection:bg-cyan-500 selection:text-slate-950 antialiased grid-pattern" x-data="marketingApp()" x-init="init()">

        @include('welcome.partials.announcement-bar')
        @include('welcome.partials.navigation-header')

        <!-- Main Content Area -->
        <main class="flex-1">
            @include('welcome.partials.hero-section')
            @include('welcome.partials.comparison-section')
            @include('welcome.partials.demo-section')
            @include('welcome.partials.features-section')
            @include('welcome.partials.roi-section')
            @include('welcome.partials.pricing-section')
            @include('welcome.partials.faq-section')
            @include('welcome.partials.cta-section')
        </main>

        @include('welcome.partials.footer-section')

        <!-- Decoupled Alpine.js Application Logic -->
        <script src="{{ asset('js/welcome/welcome-app.js') }}?v={{ file_exists(public_path('js/welcome/welcome-app.js')) ? filemtime(public_path('js/welcome/welcome-app.js')) : time() }}"></script>
    </body>
</html>
