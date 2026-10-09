<!-- Navigation Controller (Placed AFTER card info) -->
<div class="flex flex-col gap-2 max-w-lg mx-auto">
    <div class="flex items-center justify-between bg-white dark:bg-slate-900 px-6 py-3.5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <button @click="prevCard()" :disabled="currentCardIndex <= 0" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed text-slate-700 dark:text-slate-200 rounded-2xl font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Prev Card
        </button>

        <div class="text-center">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1.5">
                <span>Slide Counter</span>
                <span x-show="loadingMoreCards" class="inline-flex items-center gap-1 text-[10px] text-blue-500 font-semibold animate-pulse" title="Loading more cards in background...">
                    <svg class="animate-spin w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>Loading...</span>
                </span>
            </div>
            <div class="text-sm font-black text-slate-900 dark:text-white mt-0.5">
                <span class="text-blue-600 dark:text-cyan-400 font-mono" x-text="items.length > 0 ? (currentCardIndex + 1) : 0"></span>
                <span class="text-slate-400">/</span>
                <span class="text-slate-600 dark:text-slate-300 font-mono" x-text="pagination.total || items.length"></span>
            </div>
        </div>

        <button @click="nextCard()" :disabled="currentCardIndex >= items.length - 1 && (!pagination.last_page || pagination.current_page >= pagination.last_page)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-30 disabled:cursor-not-allowed text-white rounded-2xl font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-blue-500/20">
            Next Card
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <!-- Subtle deck progress line -->
    <div class="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
        <div class="bg-blue-600 dark:bg-cyan-500 h-full transition-all duration-300 rounded-full"
             :style="'width: ' + (items.length > 0 ? Math.min(100, Math.round(((currentCardIndex + 1) / (pagination.total || items.length)) * 100)) : 0) + '%;'">
        </div>
    </div>
</div>

<!-- Slide Navigation Dots (dynamic sliding window around current card) -->
<div class="flex flex-wrap items-center justify-center gap-1.5 max-w-md mx-auto pt-1">
    <template x-for="dot in getVisibleCardDots()" :key="dot.id">
        <button @click="currentCardIndex = dot.index"
                :class="dot.index === currentCardIndex ? 'bg-blue-600 dark:bg-cyan-400 w-6 h-2 rounded-full shadow-sm' : 'bg-slate-300 dark:bg-slate-700 hover:bg-slate-400 w-2 h-2 rounded-full'"
                class="transition-all duration-200 focus:outline-none"
                :title="'Go to card ' + (dot.index + 1)">
        </button>
    </template>
</div>
