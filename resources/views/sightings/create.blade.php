<x-app-layout>
    <x-slot name="header">
        <p class="font-medium text-forest">Field record</p>
        <h2 class="font-heading text-3xl font-bold sm:text-4xl">Log a <span class="font-accent italic text-forest">sighting</span></h2>
        <p class="mt-2 max-w-2xl text-gray-700">Record where you found a plant. Not sure what it is? <a href="{{ route('identifications.create') }}" class="font-medium text-forest underline">Scan it first</a>. An expert reviewer confirms every record.</p>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
        <form method="POST" action="{{ route('sightings.store') }}" enctype="multipart/form-data" class="card bg-sand/70">
            @include('sightings._form', ['submit' => 'Submit sighting'])
        </form>
    </div>
</x-app-layout>
