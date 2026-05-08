@props(['active'])

@php
$classes = "space-x-3 px-4 h-10 w-full flex items-center hover:bg-gray-100 dark:hover:bg-gray-700 text-slate-600 dark:text-slate-300 text-sm rounded-md" . (($active ?? false) ? ' bg-gray-100 dark:bg-gray-700' : '');
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $icon }}
    <span class="tracking-widest">{{ $name }}</span>
</a>
