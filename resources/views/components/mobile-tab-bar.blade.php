{{-- Thumb-reach navigation for signed-in users on phones; the scanner is the primary action. --}}
@php
    $tabs = [
        ['Home', 'dashboard', ['dashboard'], 'M3 11l9-7 9 7v9a1 1 0 01-1 1h-5v-6h-6v6H4a1 1 0 01-1-1z'],
        ['Plants', 'plants.index', ['plants.*'], 'M5 21c0-9 5-15 16-16-1 11-7 16-16 16zm0 0c3-5 7-9 12-12'],
        null,
        ['Map', 'map', ['map'], 'M9 4L3 6v14l6-2 6 2 6-2V4l-6 2-6-2zm0 0v14m6-12v14'],
        ['Me', 'history.index', ['history.*', 'sightings.*', 'notifications.*', 'profile.*', 'community.*', 'review.*', 'identifications.index'], 'M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0'],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-forest/15 bg-sand/95 backdrop-blur lg:hidden" style="padding-bottom: env(safe-area-inset-bottom)" aria-label="Primary">
    <ul class="mx-auto grid max-w-md grid-cols-5 items-end px-2">
        @foreach ($tabs as $tab)
            @if ($tab === null)
                <li class="flex justify-center">
                    <a href="{{ route('identifications.create') }}" class="-mt-6 flex h-16 w-16 flex-col items-center justify-center rounded-full bg-forest text-cream shadow-lg ring-4 ring-cream active:scale-95" aria-label="Open scanner">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8a2 2 0 012-2h2l2-2h6l2 2h2a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>
                        <span class="text-[10px] font-semibold">Scan</span>
                    </a>
                </li>
            @else
                @php([$label, $route, $patterns, $icon] = $tab)
                @php($active = request()->routeIs(...$patterns))
                <li>
                    <a href="{{ route($route) }}" @class([
                        'flex min-h-14 flex-col items-center justify-center gap-0.5 text-[11px] font-medium',
                        'text-forest' => $active,
                        'text-gray-600' => ! $active,
                    ]) @if ($active) aria-current="page" @endif>
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="{{ $active ? 2.2 : 1.7 }}" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                        {{ $label }}
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
</nav>
