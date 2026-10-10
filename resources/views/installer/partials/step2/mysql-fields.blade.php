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
