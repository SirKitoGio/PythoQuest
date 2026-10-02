<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="bg-cream font-sans text-gray-900 antialiased">
        <div class="min-h-screen">
            @include('layouts.navigation')

            @isset($header)
                <header class="mx-auto max-w-6xl px-4 pt-10 sm:px-6">
                    {{ $header }}
                </header>
            @endisset

            @if (session('status') && ! request()->routeIs('profile.*'))
                <div class="mx-auto mt-6 max-w-6xl px-4 sm:px-6">
                    <div class="rounded-xl bg-leaf/25 px-5 py-3 text-sm font-medium text-forest-dark">{{ session('status') }}</div>
                </div>
            @endif

            <main>
                {{ $slot }}
            </main>
        </div>

        <x-site-footer />
    </body>
</html>
