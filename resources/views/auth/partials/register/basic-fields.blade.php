<!-- Name Input -->
<div>
    <label for="name" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
        Full Name
    </label>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <input id="name" 
               type="text" 
               name="name" 
               x-model="name"
               required 
               autofocus 
               autocomplete="name"
               placeholder="e.g. Rahul Kumar"
               class="glass-input w-full pl-10 pr-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none @error('name') border-rose-500/80 ring-2 ring-rose-500/20 @enderror">
    </div>
    @error('name')
        <p class="text-rose-400 text-xs font-semibold mt-1.5 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>

<!-- Email Input -->
<div>
    <label for="email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
        Official Email
    </label>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
            </svg>
        </div>
        <input id="email" 
               type="email" 
               name="email" 
               x-model="email"
               required 
               autocomplete="username"
               placeholder="operator@nbpdcl-saas.com"
               class="glass-input w-full pl-10 pr-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none @error('email') border-rose-500/80 ring-2 ring-rose-500/20 @enderror">
    </div>
    @error('email')
        <p class="text-rose-400 text-xs font-semibold mt-1.5 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>

<!-- Mobile Phone (Optional) -->
<div>
    <label for="phone" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
        Mobile Phone <span class="text-[10px] text-slate-500 lowercase font-normal">(optional)</span>
    </label>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
        </div>
        <input id="phone" 
               type="tel" 
               name="phone" 
               x-model="phone"
               placeholder="9876543210"
               class="glass-input w-full pl-10 pr-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none @error('phone') border-rose-500/80 ring-2 ring-rose-500/20 @enderror">
    </div>
</div>
