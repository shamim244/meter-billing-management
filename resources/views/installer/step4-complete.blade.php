@extends('installer.layout', ['currentStep' => 4, 'title' => 'Installation Complete'])

@section('content')
<div class="text-center space-y-6 py-4">
    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-500/10 border-2 border-emerald-500/30 text-emerald-400 text-4xl shadow-2xl shadow-emerald-500/20">
        🎉
    </div>

    <div>
        <h2 class="text-xl font-black text-white">
            Installation Finished Successfully!
        </h2>
        <p class="text-xs text-slate-400 mt-1.5 max-w-md mx-auto">
            Your NBPDCL Meter Billing platform is now fully deployed, configured, and security-locked.
        </p>
    </div>

    <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 max-w-md mx-auto text-left space-y-2 text-xs">
        <div class="flex items-center justify-between py-1 border-b border-slate-800/80">
            <span class="text-slate-400">Lock File Status:</span>
            <span class="font-mono text-emerald-400 font-bold">storage/installed.lock [CREATED]</span>
        </div>
        <div class="flex items-center justify-between py-1 border-b border-slate-800/80">
            <span class="text-slate-400">Installer Security:</span>
            <span class="text-emerald-400 font-bold">🔒 Locked (Re-install Disabled)</span>
        </div>
        @if(session('admin_email'))
            <div class="flex items-center justify-between py-1">
                <span class="text-slate-400">Admin Login:</span>
                <span class="font-mono text-white font-bold">{{ session('admin_email') }}</span>
            </div>
        @endif
    </div>

    <div class="pt-4 max-w-xs mx-auto">
        <a href="{{ route('login') }}" class="w-full py-3 px-6 bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-400 hover:to-cyan-400 text-slate-950 text-xs font-black rounded-xl shadow-lg shadow-emerald-500/20 transition flex items-center justify-center gap-2">
            <span>Go to Admin Login</span>
            <span>→</span>
        </a>
    </div>
</div>
@endsection
