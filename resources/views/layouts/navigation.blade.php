@php
    $links = [
        ['Dashboard', 'dashboard', 'dashboard'],
        ['Plants', 'plants.index', 'plants.*'],
        ['My Sightings', 'sightings.index', 'sightings.*'],
    ];
@endphp

<nav x-data="{ open: false }" class="mx-auto max-w-6xl px-4 pt-6 sm:px-6">
    <div class="flex h-[72px] items-center justify-between rounded-2xl bg-sand px-5 shadow-sm sm:px-8">
        <div class="flex items-center gap-10">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <x-application-logo class="h-10 w-10" />
                <span class="font-heading text-xl font-bold text-forest">PhytoQuest</span>
            </a>

            <div class="hidden gap-7 sm:flex">
                @foreach ($links as [$label, $route, $pattern])
                    <x-nav-link :href="route($route)" :active="request()->routeIs($pattern)">{{ $label }}</x-nav-link>
                @endforeach
            </div>
        </div>

        <div class="hidden sm:flex sm:items-center sm:gap-4">
            <a href="{{ route('sightings.create') }}" class="rounded-lg bg-forest px-4 py-2 text-sm font-medium text-cream hover:bg-forest-dark">Open Scanner</a>

            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:text-forest focus:outline-none">
                        <div>{{ Auth::user()->name }}</div>
                        <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>

        <button @click="open = ! open" class="rounded-md p-2 text-forest sm:hidden" aria-label="Toggle menu">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="mt-2 hidden rounded-2xl bg-sand py-2 sm:hidden">
        @foreach ($links as [$label, $route, $pattern])
            <x-responsive-nav-link :href="route($route)" :active="request()->routeIs($pattern)">{{ $label }}</x-responsive-nav-link>
        @endforeach
        <x-responsive-nav-link :href="route('sightings.create')">Open Scanner</x-responsive-nav-link>

        <div class="mt-2 border-t border-forest/20 pt-3">
            <div class="px-4">
                <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                <div class="text-sm text-gray-600">{{ Auth::user()->email }}</div>
            </div>
            <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('Log Out') }}
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
