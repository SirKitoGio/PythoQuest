<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 class="font-heading text-3xl font-bold sm:text-4xl">My <span class="font-accent italic text-forest">scans</span></h2>
            <a href="{{ route('identifications.create') }}" class="btn-primary">New scan</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        @if ($identifications->isEmpty())
            <p class="py-10 text-center text-gray-700">No scans yet. <a href="{{ route('identifications.create') }}" class="font-semibold text-forest hover:underline">Identify your first plant.</a></p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($identifications as $identification)
                    @include('identifications._card')
                @endforeach
            </div>
            <div class="mt-8">{{ $identifications->links() }}</div>
        @endif
    </div>
</x-app-layout>
