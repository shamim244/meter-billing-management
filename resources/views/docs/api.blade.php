<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>NBPDCL Developer Portal & REST API Documentation (v1)</title>
    <meta name="description" content="Official REST API documentation and automation guides for NBPDCL/BSPHCL electricity billing, field reader scripts, mobile clients, and AI agents.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Alpine.js & Tailwind CSS CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/docs/api-docs.css') }}?v={{ file_exists(public_path('css/docs/api-docs.css')) ? filemtime(public_path('css/docs/api-docs.css')) : time() }}">
</head>
<body x-data="devPortal(window.devPortalConfig)" class="font-sans antialiased bg-slate-950 text-slate-100 min-h-screen flex flex-col selection:bg-brand-500 selection:text-white">

    @include('docs.partials.header-nav')
    @include('docs.partials.user-banner')

    <!-- Documentation Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full grid grid-cols-1 lg:grid-cols-12 gap-8">
        @include('docs.partials.sidebar')

        <!-- Main Documentation Content -->
        <main class="lg:col-span-9 space-y-12">
            @include('docs.partials.quickstart-section')
            @include('docs.partials.endpoints-catalog-section')
            @include('docs.partials.reason-codes-section')
            @include('docs.partials.try-it-out-console')
            @include('docs.partials.ai-copilot-section')
        </main>
    </div>

    @include('docs.partials.footer')

    <script>
        window.devPortalConfig = {
            baseUrl: '{{ $baseUrl }}',
            initialApiKey: '{{ $activeKey ? session("new_api_key")["plain_text_token"] ?? "nbp_live_NVdxdbAW6UImuxuyFDzEr5xGXq3APyt5W7kU8O10" : "" }}'
        };
    </script>
    <script src="{{ asset('js/docs/api-docs-app.js') }}?v={{ file_exists(public_path('js/docs/api-docs-app.js')) ? filemtime(public_path('js/docs/api-docs-app.js')) : time() }}"></script>
</body>
</html>
