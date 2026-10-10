<!-- Form 2: Admin Password Reset -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <div class="border-b border-slate-800 pb-4">
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
            <span>🔑</span> Admin Password Reset
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">Directly set a new password for this user if they are locked out or need credentials rotated.</p>
    </div>

    <form method="POST" action="{{ route('admin.users.update-password', $user) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- New Password -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    New Password <span class="text-rose-400">*</span>
                </label>
                <input type="password" name="password" required minlength="8" placeholder="At least 8 characters" class="w-full text-xs sm:text-sm bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500 font-mono">
                @error('password')
                    <p class="text-xs text-rose-400 mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                    Confirm New Password <span class="text-rose-400">*</span>
                </label>
                <input type="password" name="password_confirmation" required minlength="8" placeholder="Repeat new password" class="w-full text-xs sm:text-sm bg-slate-900 border-slate-800 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:ring-indigo-500 focus:border-indigo-500 font-mono">
            </div>
        </div>

        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-800">
            <button type="submit" onclick="return confirm('Change password for {{ $user->name }}?');" class="px-6 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-md shadow-amber-600/30 transition flex items-center gap-1.5">
                <span>⚡</span> Reset User Password
            </button>
        </div>
    </form>
</div>
