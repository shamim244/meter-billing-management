<!-- Section 5: Connection Endpoints & Credentials -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <div class="border-b border-slate-800/80 pb-4">
        <h2 class="text-base font-bold text-white flex items-center gap-2">
            <span>🔐</span>
            <span>Connection Endpoints & Encryption Key</span>
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">
            Network endpoints and cryptographic passphrase required for OpenSSL EVP_BytesToKey AES-256-CBC payloads.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                WSS FluentGrid API Endpoint
            </label>
            <input type="url" name="wss_url" value="{{ old('wss_url', $settings['wss_url']) }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                WSS AES Passphrase / Key
            </label>
            <div class="relative" x-data="{ showKey: false }">
                <input :type="showKey ? 'text' : 'password'" name="aes_key" value="{{ old('aes_key', $settings['aes_key']) }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:ring-indigo-500 focus:border-indigo-500 pr-10">
                <button type="button" @click="showKey = !showKey" class="absolute right-3 top-2.5 text-slate-500 hover:text-slate-300 text-xs">
                    <span x-text="showKey ? '🙈' : '👁️'"></span>
                </button>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                Legacy BSPHCL ASMX Endpoint
            </label>
            <input type="url" name="legacy_url" value="{{ old('legacy_url', $settings['legacy_url']) }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:ring-indigo-500 focus:border-indigo-500">
        </div>
    </div>
</div>
