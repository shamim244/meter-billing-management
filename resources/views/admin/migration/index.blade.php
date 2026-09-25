<x-admin-layout>
    <x-slot name="header">
        Universal Cloud Migration & Server Portability
    </x-slot>

    <div class="space-y-8" x-data="{ inspectModal: false, manifestData: null }">
        <!-- Top Toolbar & Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl">
            <div>
                <h1 class="text-xl font-black text-white flex items-center gap-3">
                    <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-lg">🚀</span>
                    Zero-Vendor-Lock-in Migration Engine
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Migrate effortlessly across <strong>Type 1 (Shared Hosting)</strong>, <strong>Type 2 (Docker)</strong>, and <strong>Type 3 (Native VPS)</strong> with 100% data fidelity and cryptographic SHA-256 verification.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 text-[11px] font-bold rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    Type 1: Shared Hosting
                </span>
                <span class="px-3 py-1 text-[11px] font-bold rounded-lg bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                    Type 2: Docker
                </span>
                <span class="px-3 py-1 text-[11px] font-bold rounded-lg bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    Type 3: Native VPS
                </span>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-800/60 text-emerald-300 text-xs font-semibold flex items-center gap-3">
                <span class="text-base">✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('warning'))
            <div class="p-4 rounded-2xl bg-amber-950/60 border border-amber-800/60 text-amber-300 text-xs font-semibold flex items-center gap-3">
                <span class="text-base">⚠️</span>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-800/60 text-rose-300 text-xs font-semibold flex items-center gap-3">
                <span class="text-base">❌</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Server Environment Preflight Card -->
        <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-black text-white flex items-center gap-2">
                    <span>🔍</span> Current Server Health & Migration Readiness
                </h2>
                @if($preflight['ready'])
                    <span class="px-3 py-1 text-[10px] font-black uppercase rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> 100% Ready
                    </span>
                @else
                    <span class="px-3 py-1 text-[10px] font-black uppercase rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        Attention Needed
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                    <span class="text-slate-400 block text-[11px]">PHP Version</span>
                    <span class="text-white font-bold text-sm">{{ $preflight['php_version'] }}</span>
                    <span class="text-[10px] block mt-0.5 {{ $preflight['php_satisfies'] ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $preflight['php_satisfies'] ? '✓ Satisfies >= 8.3' : '✗ Upgrade Required' }}
                    </span>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                    <span class="text-slate-400 block text-[11px]">Database Connection</span>
                    <span class="text-white font-bold text-sm uppercase">{{ $preflight['database']['driver'] }}</span>
                    <span class="text-[10px] block mt-0.5 {{ $preflight['database']['connected'] ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $preflight['database']['connected'] ? '✓ Connected & Active' : '✗ Failed' }}
                    </span>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                    <span class="text-slate-400 block text-[11px]">Redis Cache / Queue</span>
                    <span class="text-white font-bold text-sm">{{ $preflight['redis']['connected'] ? 'Online' : 'Offline' }}</span>
                    <span class="text-[10px] block mt-0.5 {{ $preflight['redis']['connected'] ? 'text-emerald-400' : 'text-slate-500' }}">
                        {{ $preflight['redis']['connected'] ? '✓ In-Memory Accelerated' : '• Optional (File fallback)' }}
                    </span>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80">
                    <span class="text-slate-400 block text-[11px]">Memory & Upload Limit</span>
                    <span class="text-white font-bold text-sm">{{ $preflight['memory_limit'] }}</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Upload: {{ $preflight['upload_max_filesize'] }}</span>
                </div>
            </div>

            <!-- Extensions badges -->
            <div class="mt-4 pt-4 border-t border-slate-800 flex flex-wrap gap-2 text-[11px]">
                <span class="text-slate-400 font-semibold self-center mr-2">Extensions:</span>
                @foreach($preflight['extensions'] as $ext => $loaded)
                    <span class="px-2 py-0.5 rounded-md font-mono text-[10px] font-bold border {{ $loaded ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20' : 'bg-rose-500/10 text-rose-300 border-rose-500/20' }}">
                        {{ $ext }} {{ $loaded ? '✓' : '✗' }}
                    </span>
                @endforeach
            </div>
        </div>

        <!-- 2 Column Migration Operations (Export & Import) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Column 1: Export Package -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <span class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold">📦</span>
                        <div>
                            <h2 class="text-base font-black text-white">Export Migration Package</h2>
                            <p class="text-xs text-slate-400">Generate a universal bundle to move to a new cloud server</p>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-2 text-xs text-slate-300 mb-6">
                        <div class="flex items-center justify-between py-1 border-b border-slate-800">
                            <span class="text-slate-400">Database Dump:</span>
                            <span class="font-bold text-white">Full MySQL Atomic Snapshot (.sql.gz)</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-slate-800">
                            <span class="text-slate-400">Persistent Media:</span>
                            <span class="font-bold text-white">All Bill PDFs & Attachments (.zip)</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-slate-800">
                            <span class="text-slate-400">Verification Audit:</span>
                            <span class="font-bold text-indigo-400">SHA-256 Hashes & Exact Row Manifest</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span class="text-slate-400">Compatibility:</span>
                            <span class="font-bold text-emerald-400">Universal (Shared Hosting, Docker, VPS)</span>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.server_migration.export') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <input type="checkbox" id="skip_storage_export" name="skip_storage" value="1" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                        <label for="skip_storage_export">Skip bill PDFs / storage (Database only export for rapid testing)</label>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-black shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2">
                        <span>📥</span>
                        <span>Generate & Download Migration Package</span>
                    </button>
                </form>
            </div>

            <!-- Column 2: Import Package -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <span class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold">📥</span>
                        <div>
                            <h2 class="text-base font-black text-white">Import & Rehydrate Server</h2>
                            <p class="text-xs text-slate-400">Restore a bundle and execute post-flight verification audit</p>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-amber-950/20 border border-amber-800/40 text-xs text-amber-200/90 mb-6 space-y-2">
                        <p class="font-bold flex items-center gap-1.5 text-amber-300">
                            <span>⚠️</span> Caution: Server Overwrite Protection
                        </p>
                        <p>
                            Restoring an archive replaces current tables with the imported snapshot and synchronizes all bill files. The post-flight auditor verifies that 100% of rows match the manifest before completing.
                        </p>
                    </div>
                </div>

                <form action="{{ route('admin.server_migration.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return confirm('Restore this migration bundle? This will import the database and media assets into this server.');">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-2">Select Migration Package (.zip)</label>
                        <input type="file" name="bundle" accept=".zip" required class="block w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-indigo-400 hover:file:bg-slate-800 cursor-pointer border border-slate-800 rounded-xl bg-slate-900/60 p-1">
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <input type="checkbox" id="skip_storage_import" name="skip_storage" value="1" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                        <label for="skip_storage_import">Database only (do not unpack storage files)</label>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-black shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2">
                        <span>⚡</span>
                        <span>Restore & Execute Verification Audit</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Section 3: Existing Server Packages -->
        <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl">
            <h2 class="text-sm font-black text-white flex items-center gap-2 mb-4">
                <span>📂</span> Existing Migration Bundles on this Host (storage/app/migrations)
            </h2>

            @if(empty($bundles))
                <div class="p-8 text-center text-slate-500 text-xs">
                    No migration bundles stored locally. Click "Generate & Download" above or run <code class="font-mono text-indigo-400">php artisan app:migration-pack</code>.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 font-bold">
                                <th class="pb-3 px-4">Filename</th>
                                <th class="pb-3 px-4">Size</th>
                                <th class="pb-3 px-4">Created</th>
                                <th class="pb-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach($bundles as $b)
                                <tr class="hover:bg-slate-900/40 transition">
                                    <td class="py-3 px-4 font-mono text-indigo-400 font-semibold">{{ $b['filename'] }}</td>
                                    <td class="py-3 px-4 text-slate-300 font-bold">{{ $b['size'] }}</td>
                                    <td class="py-3 px-4 text-slate-400">{{ $b['timestamp'] }}</td>
                                    <td class="py-3 px-4 text-right space-x-2">
                                        <a href="{{ route('admin.server_migration.download', ['filename' => $b['filename']]) }}" class="px-2.5 py-1 text-[11px] font-bold text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/20 rounded-lg transition inline-flex items-center gap-1">
                                            <span>⬇️</span> Download
                                        </a>
                                        <form action="{{ route('admin.server_migration.destroy', ['filename' => $b['filename']]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this migration bundle file?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 text-[11px] font-bold text-rose-400 hover:text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 rounded-lg transition">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Section 4: Mobile App Endpoint Discovery -->
        <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-10 h-10 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 font-bold">📱</span>
                <div>
                    <h2 class="text-sm font-black text-white">Flutter Mobile App Domain Discovery Endpoint</h2>
                    <p class="text-xs text-slate-400">Mobile apps in the field query this API to dynamically discover server IP / URL changes without app updates</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-[11px] text-slate-400 block">Active Discovery URL (GET):</span>
                    <code class="font-mono text-cyan-400 text-xs font-bold">{{ url('/api/v1/app/config') }}</code>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ url('/api/v1/app/config') }}" target="_blank" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                        <span>🔗</span> Test Response
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
