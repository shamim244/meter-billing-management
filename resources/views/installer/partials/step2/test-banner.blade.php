<!-- Live Test Status Banner -->
<div x-show="testMessage" x-cloak class="p-4 rounded-2xl text-xs font-semibold flex items-center gap-3 transition-all"
     :class="testSuccess ? 'bg-emerald-950/60 border border-emerald-800/80 text-emerald-300' : 'bg-rose-950/60 border border-rose-800/80 text-rose-300'">
    <span class="text-base" x-text="testSuccess ? '✅' : '❌'"></span>
    <span x-text="testMessage"></span>
</div>
