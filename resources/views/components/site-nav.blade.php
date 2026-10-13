@php
    $links = [
        'Home' => route('home'),
        'Plants' => route('plants.index'),
        'Map' => route('map'),
        'Scanner' => route('identifications.create'),
        'About' => route('about'),
        'Contact' => route('contact'),
    ];
@endphp

<nav x-data="{ open: false }" class="relative z-20 mx-auto max-w-6xl px-4 pt-6 sm:px-6">
    <div class="flex items-center justify-between rounded-2xl bg-sand px-5 py-4 shadow-sm sm:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <x-application-logo class="h-10 w-10" />
            <span class="font-heading text-xl font-bold text-forest">PhytoQuest</span>
        </a>

        <div class="hidden items-center gap-7 md:flex">
            @foreach ($links as $label => $href)
                <a href="{{ $href }}" @class([
                    'text-sm font-medium transition hover:text-forest',
                    'text-forest underline decoration-leaf decoration-2 underline-offset-8' => url()->current() === $href,
                    'text-gray-800' => url()->current() !== $href,
                ])>{{ $label }}</a>
            @endforeach
        </div>

        <div class="hidden items-center gap-3 md:flex">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-lg bg-forest px-4 py-2 text-sm font-medium text-cream hover:bg-forest-dark">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-sm font-medium text-gray-700 hover:text-forest">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-sm font-medium text-gray-800 hover:text-forest">Login</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-forest px-4 py-2 text-sm font-medium text-cream hover:bg-forest-dark">Sign Up</a>
            @endauth
        </div>

        <button @click="open = !open" class="rounded-md p-2 text-forest md:hidden" aria-label="Toggle menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!open" stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path x-show="open" stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div x-show="open" x-cloak class="mt-2 space-y-1 rounded-2xl bg-sand p-4 md:hidden">
        @foreach ($links as $label => $href)
            <a href="{{ $href }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-800 hover:bg-cream">{{ $label }}</a>
        @endforeach
        <div class="border-t border-forest/20 pt-2">
            @auth
                <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-forest">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-gray-800">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-800">Login</a>
                <a href="{{ route('register') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-forest">Sign Up</a>
            @endauth
        </div>
    </div>
</nav>
