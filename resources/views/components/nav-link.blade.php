@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-bold bg-brand-500/10 dark:bg-brand-500/15 text-brand-600 dark:text-cyan-400 border border-brand-500/20 shadow-sm transition-all duration-150'
            : 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
