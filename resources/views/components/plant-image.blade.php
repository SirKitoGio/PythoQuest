@props(['plant' => null, 'src' => null, 'lazy' => false])

@php($src ??= $plant?->photoUrl())

<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-gradient-to-br from-leaf/70 to-forest']) }}>
    {{-- Placeholder sits underneath; a missing or failed image simply reveals it. --}}
    <div class="absolute inset-0 flex items-center justify-center">
        <svg class="h-14 w-14 text-cream/90" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 21c0-9 5-15 16-16-1 11-7 16-16 16z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 21c3-5 7-9 12-12"/>
        </svg>
    </div>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $plant?->common_name }}" @if ($lazy) loading="lazy" @endif decoding="async"
            onerror="this.remove()" class="relative h-full w-full object-cover">
    @endif
</div>
