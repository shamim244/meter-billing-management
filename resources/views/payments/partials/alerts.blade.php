<!-- Client-side Error Alert -->
<div x-show="errorMessage" x-cloak class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
    <span x-text="'❌ ' + errorMessage"></span>
    <button type="button" @click="errorMessage = null" class="text-rose-600 dark:text-rose-400 font-bold">✕</button>
</div>

<!-- Server Flash Errors -->
@if($errors->any())
    <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold space-y-1">
        <div class="font-bold flex items-center gap-1.5">
            <span>❌</span> Please correct the following errors:
        </div>
        <ul class="list-disc list-inside pl-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('error'))
    <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
        <span>❌ {{ session('error') }}</span>
        <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 font-bold">✕</button>
    </div>
@endif
