{{-- Compose Modal --}}
<div x-show="composeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div @click.away="closeComposeModal()" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white">⚡ Send Outbound Email (Hostinger API)</h3>
            <button @click="closeComposeModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.notifications.mailbox.send') }}" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">From Mailbox</label>
                <select name="from_address" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500 font-mono">
                    @foreach($mailboxes as $mb)
                        <option value="{{ $mb['address'] }}" {{ $mb['address'] === $selectedAddress ? 'selected' : '' }}>{{ $mb['address'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Recipient Email</label>
                <input type="email" name="to" required placeholder="user@example.com" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500" />
            </div>

            <div>
                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Subject</label>
                <input type="text" name="subject" required placeholder="Important Notice Regarding Your Billing Cycle" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500" />
            </div>

            <div>
                <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">HTML / Text Message Body</label>
                <textarea name="body" rows="5" required placeholder="Enter message content..." class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500 font-mono"></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-2 border-t border-slate-800">
                <button type="button" @click="closeComposeModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-indigo-600 text-slate-950 font-black rounded-xl text-xs transition shadow-md shadow-cyan-500/20">🚀 Send via Hostinger</button>
            </div>
        </form>
    </div>
</div>
