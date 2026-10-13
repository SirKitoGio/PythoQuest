@props(['active'])

@php
$classes = ($active ?? false)
            ? 'mx-2 block rounded-lg bg-leaf/30 px-3 py-2.5 text-start text-base font-medium text-forest focus:outline-none transition duration-150 ease-in-out'
            : 'mx-2 block rounded-lg px-3 py-2.5 text-start text-base font-medium text-gray-700 hover:bg-cream hover:text-forest focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
