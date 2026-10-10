<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
    <div>
        <h2 class="text-2xl font-black text-white tracking-tight">Endpoints Catalog</h2>
        <p class="text-xs text-slate-400 mt-1">Select your preferred programming language below to update all code snippets dynamically.</p>
    </div>

    <!-- Global Language Switcher Tabs -->
    <div class="flex items-center p-1 rounded-xl bg-slate-900 border border-slate-800 font-semibold text-xs shrink-0">
        <button @click="activeLang = 'python'" :class="activeLang === 'python' ? 'bg-brand-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="px-3 py-1.5 rounded-lg transition">
            🐍 Python
        </button>
        <button @click="activeLang = 'curl'" :class="activeLang === 'curl' ? 'bg-brand-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="px-3 py-1.5 rounded-lg transition">
            💻 cURL
        </button>
        <button @click="activeLang = 'javascript'" :class="activeLang === 'javascript' ? 'bg-brand-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="px-3 py-1.5 rounded-lg transition">
            🟨 Node.js
        </button>
        <button @click="activeLang = 'dart'" :class="activeLang === 'dart' ? 'bg-brand-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="px-3 py-1.5 rounded-lg transition">
            🎯 Dart
        </button>
    </div>
</div>
