<span class="font-mono text-cyan-200 text-xs sm:text-sm font-semibold select-text select-all cursor-pointer hover:text-white transition py-0.5" 
      @click="copyText(bill.ca_number, bill.id)" 
      title="Tap to copy or long-press to select CA"
      x-text="bill.ca_number"></span>
<button type="button" 
        @click.stop="copyText(bill.ca_number, bill.id)" 
        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold transition border select-none active:scale-95 touch-manipulation"
        :class="copiedCaId === bill.id ? 'bg-emerald-500/40 border-emerald-400 text-emerald-100' : 'bg-white/10 hover:bg-white/20 text-cyan-200 border-white/20'"
        title="Copy CA to clipboard">
    <template x-if="copiedCaId !== bill.id">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
    </template>
    <template x-if="copiedCaId === bill.id">
        <svg class="w-3 h-3 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </template>
    <span x-text="copiedCaId === bill.id ? 'Copied!' : 'Copy'"></span>
    <span x-show="copiedCaId !== bill.id" class="hidden sm:inline-block text-[8px] opacity-75 font-mono" x-text="'[' + (shortcuts.copy_ca?.toUpperCase() || 'C') + ']'"></span>
</button>
