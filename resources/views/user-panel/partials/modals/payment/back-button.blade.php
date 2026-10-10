<!-- Back to Step 2 Button -->
<div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-start">
    <button type="button" @click="goToStep(2)" class="py-2 px-4 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs transition cursor-pointer">
        <span x-text="isSamePlan ? '⬅ Back to Duration' : '⬅ Back to Comparison'"></span>
    </button>
</div>
