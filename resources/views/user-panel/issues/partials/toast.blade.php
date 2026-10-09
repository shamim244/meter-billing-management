<!-- Toast Notification for Copy -->
<div x-show="toastMessage" 
     x-cloak 
     x-transition 
     class="fixed bottom-6 right-6 z-50 px-4 py-2.5 rounded-2xl bg-slate-900 text-white text-xs font-bold shadow-xl flex items-center gap-2">
    <span>📋</span>
    <span x-text="toastMessage"></span>
</div>
