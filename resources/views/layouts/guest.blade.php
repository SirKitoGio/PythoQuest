<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="bg-cream font-sans text-gray-900 antialiased">
        <div class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-8">
            <div class="grid w-full max-w-6xl overflow-hidden rounded-2xl shadow-xl md:min-h-[720px] md:grid-cols-2">
                <aside class="relative flex flex-col justify-between bg-forest p-8 text-cream sm:p-12">
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <x-application-logo class="h-11 w-11 rounded-full ring-2 ring-leaf" />
                        <span class="font-heading text-xl font-bold">PhytoQuest</span>
                    </a>

                    <div class="my-10 md:my-0">
                        @isset($aside)
                            {{ $aside }}
                        @else
                            <h1 class="font-heading text-4xl font-bold leading-tight sm:text-5xl">Find Wonder <span class="block font-accent italic text-leaf">Where You Stand</span></h1>
                        @endisset
                    </div>

                    <p class="text-sm text-cream/70">For research and education only. No medical advice.</p>

                    <svg class="pointer-events-none absolute -bottom-10 -right-10 h-64 w-64 text-leaf/20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M5 21c0-9 5-15 16-16-1 11-7 16-16 16zm2-2c3-4 6-7 10-10"/>
                    </svg>
                </aside>

                <section class="flex items-center bg-sand p-8 sm:p-14">
                    <div class="mx-auto w-full max-w-md">
                        {{ $slot }}
                    </div>
                </section>
            </div>
        </div>
    </body>
</html>
