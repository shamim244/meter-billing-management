@extends('installer.layout', ['currentStep' => 2, 'title' => 'Database Configuration'])

@section('content')
<div x-data="{
    driver: 'mysql',
    host: '{{ old('host', $defaultHost) }}',
    port: '{{ old('port', $defaultPort) }}',
    database: '{{ old('database', $defaultDatabase) }}',
    username: '{{ old('username', $defaultUsername) }}',
    password: '{{ old('password') }}',
    testing: false,
    testSuccess: null,
    testMessage: '',

    testConnection() {
        this.testing = true;
        this.testSuccess = null;
        this.testMessage = '';

        fetch('{{ route('install.test_db') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
            },
            body: JSON.stringify({
                driver: this.driver,
                host: this.host,
                port: this.port,
                database: this.database,
                username: this.username,
                password: this.password
            })
        })
        .then(res => res.json())
        .then(data => {
            this.testing = false;
            this.testSuccess = data.success;
            this.testMessage = data.message;
        })
        .catch(err => {
            this.testing = false;
            this.testSuccess = false;
            this.testMessage = 'Network error or request timeout while testing connection.';
        });
    }
}" class="space-y-6">

    <div>
        <h2 class="text-lg font-black text-white flex items-center gap-2">
            <span>💾</span> Step 2: Database & Environment Connection
        </h2>
        <p class="text-xs text-slate-400 mt-1">
            Provide your database credentials. You can click <strong>Test Connection</strong> to verify access before saving.
        </p>
    </div>

    <!-- Live Test Status Banner -->
    <div x-show="testMessage" x-cloak class="p-4 rounded-2xl text-xs font-semibold flex items-center gap-3 transition-all"
         :class="testSuccess ? 'bg-emerald-950/60 border border-emerald-800/80 text-emerald-300' : 'bg-rose-950/60 border border-rose-800/80 text-rose-300'">
        <span class="text-base" x-text="testSuccess ? '✅' : '❌'"></span>
        <span x-text="testMessage"></span>
    </div>

    <form action="{{ route('install.save_db') }}" method="POST" class="space-y-4">
        @csrf

        <!-- Driver Selection -->
        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1.5">Database Driver</label>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" @click="driver = 'mysql'" :class="driver === 'mysql' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 font-bold' : 'bg-slate-950/60 border-slate-800 text-slate-400'" class="p-3 rounded-xl border text-xs text-center transition">
                    MySQL / MariaDB (Recommended)
                </button>
                <button type="button" @click="driver = 'sqlite'" :class="driver === 'sqlite' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 font-bold' : 'bg-slate-950/60 border-slate-800 text-slate-400'" class="p-3 rounded-xl border text-xs text-center transition">
                    SQLite (File-based)
                </button>
            </div>
            <input type="hidden" name="driver" :value="driver">
        </div>

        <!-- MySQL Specific Fields -->
        <div x-show="driver === 'mysql'" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-300 mb-1">Host</label>
                    <input type="text" name="host" x-model="host" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Port</label>
                    <input type="text" name="port" x-model="port" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Database Name</label>
                <input type="text" name="database" x-model="database" required placeholder="e.g. meter_billing" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
                <span class="text-[10px] text-slate-500 mt-1 block">If this database does not exist, the installer will attempt to create it automatically.</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Username</label>
                    <input type="text" name="username" x-model="username" required placeholder="root" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Password</label>
                    <input type="password" name="password" x-model="password" placeholder="••••••••" class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
                </div>
            </div>
        </div>

        <!-- App URL Field -->
        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Application URL</label>
            <input type="url" name="app_url" value="{{ old('app_url', $currentUrl) }}" required class="w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
            <span class="text-[10px] text-slate-500 mt-1 block">Full URL of this website (used for generating links, QR codes, and API endpoints).</span>
        </div>

        <!-- Connection Test & Proceed Buttons -->
        <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-800">
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('install.step1') }}" class="px-4 py-2.5 text-xs font-bold text-slate-400 hover:text-white bg-slate-950 hover:bg-slate-800 rounded-xl transition">
                    ← Back
                </a>
                <button type="button" @click="testConnection()" :disabled="testing" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl transition flex items-center gap-2">
                    <span x-show="testing" class="animate-spin text-indigo-400">⏳</span>
                    <span x-text="testing ? 'Testing...' : '🔌 Test Connection'"></span>
                </button>
            </div>

            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2">
                <span>Save & Continue</span>
                <span>→</span>
            </button>
        </div>
    </form>
</div>
@endsection
