<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-4xl font-bold">Add a <span class="font-accent italic text-forest">plant profile</span></h2>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <form method="POST" action="{{ route('plants.store') }}" class="card bg-sand/70">
            @include('plants._form', ['submit' => 'Save plant'])
        </form>
    </div>
</x-app-layout>
