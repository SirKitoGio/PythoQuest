<x-app-layout>
    <x-slot name="header">
        <p class="font-medium text-forest">Plant scanner</p>
        <h2 class="font-heading text-4xl font-bold">Log a <span class="font-accent italic text-forest">sighting</span></h2>
        <p class="mt-2 max-w-2xl text-gray-700">Automatic identification is not available yet. Pick the plant yourself; an expert reviewer will confirm it.</p>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <form method="POST" action="{{ route('sightings.store') }}" enctype="multipart/form-data" class="card bg-sand/70">
            @include('sightings._form', ['submit' => 'Submit sighting'])
        </form>
    </div>
</x-app-layout>
