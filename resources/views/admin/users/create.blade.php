<x-admin-layout>
    <x-slot name="header">
        Create New User / Billing Agent
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-sm text-indigo-400 hover:underline">
            ← Back to Users List
        </a>

        <div class="bg-slate-950 p-8 rounded-3xl border border-slate-800 shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6">User Account Details</h2>

            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
                @csrf

                <!-- Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-white focus:ring-indigo-500 focus:border-indigo-500">
                    @error('name') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-white focus:ring-indigo-500 focus:border-indigo-500">
                    @error('email') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                    <input type="password" name="password" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-white focus:ring-indigo-500 focus:border-indigo-500">
                    @error('password') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="e.g. 9876543210" class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-white focus:ring-indigo-500 focus:border-indigo-500">
                    @error('phone') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Role -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Role</label>
                        <select name="role" class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-white focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Account Status</label>
                        <select name="status" class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-white focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                        @error('status') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Submit -->
                <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 border-t border-slate-900">
                    <a href="{{ route('admin.users.index') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-400 hover:bg-slate-900 transition text-center">
                        Cancel
                    </a>
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs sm:text-sm font-bold shadow-lg shadow-indigo-600/30 transition text-center">
                        Create User Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
