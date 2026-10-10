<!-- Mobile Drawer Backdrop -->
<div x-show="sidebarOpen" 
     x-cloak 
     @click="sidebarOpen = false" 
     class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm md:hidden"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
</div>
