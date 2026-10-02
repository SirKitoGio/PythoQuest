<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-4xl font-bold">Edit <span class="font-accent italic text-forest">{{ $plant->common_name }}</span></h2>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <form method="POST" action="{{ route('plants.update', $plant) }}" class="card bg-sand/70">
            @method('PUT')
            @include('plants._form', ['submit' => 'Update plant'])
        </form>
    </div>
</x-app-layout>
