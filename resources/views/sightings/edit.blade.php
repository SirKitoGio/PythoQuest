<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-4xl font-bold">Edit <span class="font-accent italic text-forest">sighting</span></h2>
        <p class="mt-2 text-gray-700">Saving changes sends the sighting back to pending review.</p>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <form method="POST" action="{{ route('sightings.update', $sighting) }}" enctype="multipart/form-data" class="card bg-sand/70">
            @method('PUT')
            @include('sightings._form', ['submit' => 'Update sighting'])
        </form>
    </div>
</x-app-layout>
