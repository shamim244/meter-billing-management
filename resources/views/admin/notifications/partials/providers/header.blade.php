{{-- Header & Action --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>⚡</span> Email Provider Registry & Fallback Chain
        </h1>
        <p class="text-sm text-slate-400 mt-1">
            Configure multiple SMTP/API email providers in priority order. When delivery fails, the system automatically falls through to the next enabled provider.
        </p>
    </div>

    <div class="flex items-center gap-3">
        <button @click="openAddModal()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-indigo-600/30">
            <span>+</span> Add Email Provider
        </button>
    </div>
</div>
