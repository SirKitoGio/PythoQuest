<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head')
    </head>
    <body @class(["bg-cream font-sans text-gray-900 antialiased", "pb-tab-bar lg:pb-0" => auth()->check()])>
        <x-site-nav />

        @if (session('status'))
            <div class="mx-auto mt-6 max-w-6xl px-4 sm:px-6">
                <div class="rounded-xl bg-leaf/25 px-5 py-3 text-sm font-medium text-forest-dark">{{ session('status') }}</div>
            </div>
        @endif

        <main>
            {{ $slot }}
        </main>

        <x-site-footer />
        @auth
            <x-mobile-tab-bar />
        @endauth
    </body>
</html>
