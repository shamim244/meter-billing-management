{{-- Flash Alerts and Security Notices --}}
@if(session('error'))
    <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-xs text-rose-300">
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-xs text-emerald-300">
        {{ session('success') }}
    </div>
@endif

@if(!empty($hasKeyMismatch))
    <div class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-2xl text-xs text-amber-300 flex items-start gap-3 shadow-lg">
        <span class="text-lg leading-none mt-0.5">⚠️</span>
        <div class="space-y-1">
            <p class="font-bold text-amber-200">Server Encryption Key (APP_KEY) Rotated</p>
            <p class="text-[11px] text-amber-300/90 leading-relaxed">
                The application's encryption key was regenerated on the server, so previously encrypted email credentials cannot be decrypted with the new key. 
                Please click <span class="font-bold underline text-white">Edit</span> on the flagged providers below to enter and re-save your SMTP or API credentials with the current key.
            </p>
        </div>
    </div>
@endif
