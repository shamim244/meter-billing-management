<!-- MODAL 3: Direct Notification Dispatcher -->
<div x-show="showNotificationModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="showNotificationModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>📢</span> Send Direct Notification to {{ $user->name }}
            </h3>
            <button type="button" @click="showNotificationModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.users.send-notification', $user) }}" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Notification Title <span class="text-rose-400">*</span></label>
                <input type="text" name="title" required placeholder="e.g. Account Update / Action Required" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Message Body <span class="text-rose-400">*</span></label>
                <textarea name="body" rows="4" required placeholder="Enter the message you wish to send to this operator..." class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Priority</label>
                    <select name="priority" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3">
                        <option value="routine">Routine (Info)</option>
                        <option value="critical">Critical (High Importance)</option>
                        <option value="urgent">Urgent (Immediate Action)</option>
                    </select>
                </div>

                <div class="flex items-center pt-5">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-white">
                        <input type="checkbox" name="send_email" value="1" checked class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                        <span>Also send to email</span>
                    </label>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                <button type="button" @click="showNotificationModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white shadow">Dispatch Alert</button>
            </div>
        </form>
    </div>
</div>
