<x-site-layout title="Plants">
    <section class="mx-auto max-w-6xl px-6 pt-14">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="text-xl font-medium text-forest">Plant database</p>
                <h1 class="mt-1 font-heading text-5xl font-bold">Explore <span class="font-accent italic text-forest">plants</span></h1>
            </div>
            @auth
                <a href="{{ route('plants.create') }}" class="btn-primary">+ Add plant profile</a>
            @endauth
        </div>

        <form method="GET" action="{{ route('plants.index') }}" class="mt-8 flex flex-col gap-3 sm:flex-row">
            <label for="q" class="sr-only">Search</label>
            <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Search by common or scientific name"
                class="h-12 flex-1 rounded-lg border-forest/20 bg-sand/60 focus:border-forest focus:ring-leaf">
            <label for="category" class="sr-only">Category</label>
            <select id="category" name="category" class="h-12 rounded-lg border-forest/20 bg-sand/60 focus:border-forest focus:ring-leaf sm:w-52">
                <option value="">All categories</option>
                @foreach ($categories as $option)
                    <option value="{{ $option }}" @selected($category === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <button class="btn-primary h-12">Search</button>
            @if ($search || $category)
                <a href="{{ route('plants.index') }}" class="btn-secondary h-12">Clear</a>
            @endif
        </form>

        @if ($plants->isEmpty())
            <p class="mt-16 text-center text-lg text-gray-700">No plants match your search.</p>
        @else
            <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($plants as $plant)
                    <a href="{{ route('plants.show', $plant) }}" class="group overflow-hidden rounded-xl bg-sand shadow-sm ring-1 ring-forest/10 transition hover:-translate-y-1 hover:shadow-lg">
                        <x-plant-image :plant="$plant" class="h-40" />
                        <div class="p-6">
                            <h2 class="font-heading text-2xl font-semibold group-hover:text-forest">{{ $plant->common_name }}</h2>
                            <p class="italic text-gray-700">{{ $plant->scientific_name }}</p>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="badge">{{ $plant->category }}</span>
                                <span class="text-xs text-gray-600">{{ $plant->source_label }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">{{ $plants->links() }}</div>
        @endif
    </section>
</x-site-layout>
