{{-- Test Send Modal --}}
<div x-show="testModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div @click.away="closeTestModal()" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white">Direct Test Send</h3>
            <button @click="closeTestModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <p class="text-xs text-slate-400">
            Send a test email directly via <strong class="text-white" x-text="selectedProviderLabel"></strong> (bypasses fallback chain).
        </p>

        <form :action="'/admin/notifications/email-providers/' + selectedProviderId + '/test-send'" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Recipient Email</label>
                <input type="email" name="test_recipient" required placeholder="admin@example.com" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2.5 px-3 focus:ring-indigo-500" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                <button type="button" @click="closeTestModal()" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl font-bold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl font-bold">Send Test</button>
            </div>
        </form>
    </div>
</div>
