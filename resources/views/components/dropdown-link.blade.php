<a {{ $attributes->merge(['class' => 'flex items-center gap-2 w-full px-3 py-2 text-start text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white rounded-xl transition duration-150 ease-in-out cursor-pointer']) }}>
    {{ $slot }}
</a>
