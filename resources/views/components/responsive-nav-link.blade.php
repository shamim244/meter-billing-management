@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-2 w-full px-3.5 py-2.5 rounded-xl text-sm font-bold bg-brand-500/10 dark:bg-brand-500/15 text-brand-600 dark:text-cyan-400 border border-brand-500/20 shadow-sm transition'
            : 'flex items-center gap-2 w-full px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
