{{-- Breadcrumb & Back Navigation --}}
<div class="flex items-center justify-between">
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 dark:text-cyan-400 hover:underline transition">
        ← Back to Dashboard
    </a>
    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">CA: {{ $account->ca_number }}</span>
</div>
