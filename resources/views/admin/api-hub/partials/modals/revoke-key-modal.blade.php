<!-- 3. Key Revocation Modal -->
<div x-show="revokingKey" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div @click.away="revokingKey = null" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="w-12 h-12 rounded-2xl bg-rose-500/15 text-rose-400 flex items-center justify-center font-bold text-xl border border-rose-500/30 mx-auto">
            🛑
        </div>
        <div class="text-center">
            <h3 class="text-lg font-bold text-white">Emergency Revoke Key?</h3>
            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                Are you sure you want to revoke <strong class="text-white" x-text="revokingKey?.name"></strong> belonging to <strong class="text-white" x-text="revokingKey?.user"></strong>? This API key will stop working immediately.
            </p>
        </div>
        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" @click="revokingKey = null" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 rounded-xl transition">
                Cancel
            </button>
            <form :action="'{{ url('/admin/api-hub/keys') }}/' + (revokingKey?.id || '')" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-xl transition shadow-lg shadow-rose-600/30">
                    Yes, Permanently Revoke
                </button>
            </form>
        </div>
    </div>
</div>
