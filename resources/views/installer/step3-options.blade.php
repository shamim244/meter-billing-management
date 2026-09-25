@extends('installer.layout', ['currentStep' => 3, 'title' => 'Installation Setup Mode'])

@section('content')
<div x-data="{ mode: 'clean' }" class="space-y-6">
    <div>
        <h2 class="text-lg font-black text-white flex items-center gap-2">
            <span>⚙️</span> Step 3: Select Installation Mode
        </h2>
        <p class="text-xs text-slate-400 mt-1">
            Choose whether to perform a fresh clean install or restore an existing migration bundle.
        </p>
    </div>

    <!-- Mode Selector Tabs -->
    <div class="grid grid-cols-2 gap-3 p-1.5 bg-slate-950/80 rounded-2xl border border-slate-800">
        <button type="button" @click="mode = 'clean'" :class="mode === 'clean' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'" class="py-3 px-4 rounded-xl text-xs flex flex-col items-center gap-1 transition">
            <span class="text-lg">✨</span>
            <span>Option A: Clean Install</span>
            <span class="text-[10px] opacity-75 font-normal">Fresh database & new Super Admin</span>
        </button>

        <button type="button" @click="mode = 'restore'" :class="mode === 'restore' ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'" class="py-3 px-4 rounded-xl text-xs flex flex-col items-center gap-1 transition">
            <span class="text-lg">📦</span>
            <span>Option B: 1-Click Restore</span>
            <span class="text-[10px] opacity-75 font-normal">Restore migration package (.zip)</span>
        </button>
    </div>

    <!-- Mode 1: Clean Install Form -->
    <div x-show="mode === 'clean'" class="space-y-4">
        <div class="p-4 rounded-2xl bg-indigo-950/20 border border-indigo-800/40 text-xs text-indigo-200">
            <p class="font-bold mb-1">🚀 What happens next:</p>
            <p class="text-[11px] text-indigo-300/80">
                The installer will run all database schema migrations, seed initial roles and permissions, configure default system settings, and create your initial Super Admin account.
            </p>
        </div>

        <form action="{{ route('install.run_clean') }}" method="POST" class="space-y-4" onsubmit="return confirm('Ready to execute clean installation and migrate database tables?');">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Super Admin Full Name</label>
                <input type="text" name="name" value="{{ old('name', 'Super Admin') }}" required class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Super Admin Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="admin@example.com" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Admin Password (min 8 chars)</label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" required placeholder="••••••••" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            <div class="pt-4 flex items-center justify-between border-t border-slate-800">
                <a href="{{ route('install.step2') }}" class="px-4 py-2.5 text-xs font-bold text-slate-400 hover:text-white bg-slate-950 hover:bg-slate-800 rounded-xl transition">
                    ← Back
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                    <span>⚡ Run Clean Installation</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Mode 2: 1-Click Restore From Migration Bundle Form -->
    <div x-show="mode === 'restore'" x-cloak class="space-y-4">
        <div class="p-4 rounded-2xl bg-emerald-950/20 border border-emerald-800/40 text-xs text-emerald-200">
            <p class="font-bold mb-1">📦 What happens next:</p>
            <p class="text-[11px] text-emerald-300/80">
                The installer will unpack your universal migration package, restore the entire database with atomic foreign key checks, rehydrate all bill attachments and media files, and audit table row counts against the cryptographic manifest.
            </p>
        </div>

        <form action="{{ route('install.run_restore') }}" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return confirm('Restore this migration bundle? All data and users will be imported directly.');">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-2">Select Migration Package (.zip)</label>
                <input type="file" name="bundle" accept=".zip" required class="block w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-950 file:text-indigo-400 hover:file:bg-slate-800 cursor-pointer border border-slate-800 rounded-xl bg-slate-950/60 p-1">
                <span class="text-[10px] text-slate-500 mt-1 block">Accepts universal migration bundles generated via <code>php artisan app:migration-pack</code> or the Admin Migration Cockpit.</span>
            </div>

            <div class="pt-4 flex items-center justify-between border-t border-slate-800">
                <a href="{{ route('install.step2') }}" class="px-4 py-2.5 text-xs font-bold text-slate-400 hover:text-white bg-slate-950 hover:bg-slate-800 rounded-xl transition">
                    ← Back
                </a>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl shadow-lg shadow-emerald-600/30 transition flex items-center gap-2">
                    <span>📥 Unpack & Rehydrate Server</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
