<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Installation Wizard' }} — NBPDCL SaaS</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS & Alpine.js CDNs (Zero local build dependency) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans antialiased min-h-screen bg-slate-950 flex flex-col justify-between py-12 px-4 sm:px-6 lg:px-8">

    <div class="max-w-2xl w-full mx-auto space-y-8">
        <!-- Logo & Title Header -->
        <div class="text-center space-y-3">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 text-3xl shadow-xl shadow-indigo-500/25 ring-8 ring-slate-900/60 mb-2">
                ⚡
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight sm:text-3xl">
                NBPDCL SaaS Installation Wizard
            </h1>
            <p class="text-xs text-slate-400 font-medium">
                Universal Zero-Vendor-Lock-in Deployment & Server Setup
            </p>

            <!-- 4-Step Progress Bar -->
            @php
                $step = $currentStep ?? 1;
            @endphp
            <div class="pt-6 max-w-md mx-auto">
                <div class="flex items-center justify-between text-[11px] font-bold">
                    <!-- Step 1 -->
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold border-2 transition {{ $step >= 1 ? 'bg-indigo-600 border-indigo-400 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-900 border-slate-800 text-slate-500' }}">
                            {{ $step > 1 ? '✓' : '1' }}
                        </div>
                        <span class="mt-1.5 {{ $step >= 1 ? 'text-indigo-400' : 'text-slate-500' }}">Server Audit</span>
                    </div>

                    <div class="flex-1 h-0.5 mx-2 bg-slate-800 {{ $step > 1 ? 'bg-indigo-600' : '' }}"></div>

                    <!-- Step 2 -->
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold border-2 transition {{ $step >= 2 ? 'bg-indigo-600 border-indigo-400 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-900 border-slate-800 text-slate-500' }}">
                            {{ $step > 2 ? '✓' : '2' }}
                        </div>
                        <span class="mt-1.5 {{ $step >= 2 ? 'text-indigo-400' : 'text-slate-500' }}">Database</span>
                    </div>

                    <div class="flex-1 h-0.5 mx-2 bg-slate-800 {{ $step > 2 ? 'bg-indigo-600' : '' }}"></div>

                    <!-- Step 3 -->
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold border-2 transition {{ $step >= 3 ? 'bg-indigo-600 border-indigo-400 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-900 border-slate-800 text-slate-500' }}">
                            {{ $step > 3 ? '✓' : '3' }}
                        </div>
                        <span class="mt-1.5 {{ $step >= 3 ? 'text-indigo-400' : 'text-slate-500' }}">Setup Mode</span>
                    </div>

                    <div class="flex-1 h-0.5 mx-2 bg-slate-800 {{ $step > 3 ? 'bg-indigo-600' : '' }}"></div>

                    <!-- Step 4 -->
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold border-2 transition {{ $step >= 4 ? 'bg-emerald-600 border-emerald-400 text-white shadow-lg shadow-emerald-600/30' : 'bg-slate-900 border-slate-800 text-slate-500' }}">
                            4
                        </div>
                        <span class="mt-1.5 {{ $step >= 4 ? 'text-emerald-400' : 'text-slate-500' }}">Finish</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Alerts -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-800/60 text-emerald-300 text-xs font-semibold flex items-center gap-3">
                <span class="text-base">✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-800/60 text-rose-300 text-xs font-semibold flex items-center gap-3">
                <span class="text-base">❌</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Main Content Card -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
            @yield('content')
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-xs text-slate-500 py-4">
        &copy; {{ date('Y') }} NBPDCL Meter Billing & Extraction Engine. Built for Type 1 (Shared), Type 2 (Docker), & Type 3 (VPS).
    </div>

</body>
</html>
