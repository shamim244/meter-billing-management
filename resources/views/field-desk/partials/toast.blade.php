        <!-- Toast Notification -->
        <div x-show="toast.show"
             x-cloak
             x-transition
             class="fixed bottom-5 right-5 z-50 bg-slate-900 text-white text-xs px-4 py-2.5 rounded-xl shadow-lg border border-slate-700 flex items-center gap-2">
            <span x-text="toast.icon"></span>
            <span x-text="toast.message"></span>
        </div>