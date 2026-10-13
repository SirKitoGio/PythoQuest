<x-site-layout title="Plants">
    @php($select = 'h-11 rounded-lg border-forest/20 bg-sand/60 text-sm focus:border-forest focus:ring-leaf')

    <section class="mx-auto max-w-6xl px-4 pt-8 sm:px-6 sm:pt-14">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="text-lg font-medium text-forest sm:text-xl">Plant database</p>
                <h1 class="mt-1 font-heading text-4xl font-bold sm:text-5xl">Explore <span class="font-accent italic text-forest">plants</span></h1>
                <p class="mt-2 text-sm text-gray-700 sm:text-base">{{ number_format($plants->total()) }} {{ Str::plural('profile', $plants->total()) }} from curated pilot records and the Plants For A Future database.</p>
            </div>
            @can('create', App\Models\Plant::class)
                <a href="{{ route('plants.create') }}" class="btn-primary">+ Add plant profile</a>
            @endcan
        </div>

        <form method="GET" action="{{ route('plants.index') }}" class="mt-8 space-y-3" x-data="{ more: {{ count(array_diff_key($filters, ['category' => 1])) ? 'true' : 'false' }} }">
            <div class="grid grid-cols-2 gap-3 sm:flex">
                <div class="relative col-span-2 sm:flex-1">
                    <label for="q" class="sr-only">Search</label>
                    <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Search name or family — typos OK" enterkeyhint="search"
                        class="h-12 w-full rounded-lg border-forest/20 bg-sand/60 pe-14 focus:border-forest focus:ring-leaf">
                    <button class="absolute inset-y-1 end-1 flex w-11 items-center justify-center rounded-md bg-forest text-cream sm:hidden" aria-label="Search">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
                    </button>
                </div>
                <label for="category" class="sr-only">Category</label>
                <select id="category" name="category" onchange="this.form.requestSubmit()" class="{{ $select }} h-12 sm:w-48">
                    <option value="">All categories</option>
                    @foreach ($categories as $option)
                        <option value="{{ $option }}" @selected(($filters['category'] ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <button class="btn-primary hidden h-12 sm:inline-flex">Search</button>
                <button type="button" @click="more = ! more" class="btn-secondary h-12" :aria-expanded="more">
                    Filters
                    @if (count(array_diff_key($filters, ['category' => 1])))
                        <span class="rounded-full bg-forest px-1.5 text-xs text-cream">{{ count(array_diff_key($filters, ['category' => 1])) }}</span>
                    @endif
                </button>
            </div>

            <div x-show="more" x-cloak class="grid gap-3 rounded-xl bg-sand/60 p-4 ring-1 ring-forest/10 sm:grid-cols-2 lg:grid-cols-5">
                <label class="text-xs font-semibold uppercase tracking-wider text-forest">Family
                    <select name="family" class="{{ $select }} mt-1 w-full font-normal normal-case tracking-normal text-gray-900">
                        <option value="">Any family</option>
                        @foreach ($families as $family)
                            <option value="{{ $family }}" @selected(($filters['family'] ?? '') === $family)>{{ $family }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold uppercase tracking-wider text-forest">Traditional use
                    <select name="action" class="{{ $select }} mt-1 w-full font-normal normal-case tracking-normal text-gray-900">
                        <option value="">Any documented use</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action->slug }}" @selected(($filters['action'] ?? '') === $action->slug)>{{ $action->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold uppercase tracking-wider text-forest">Plant part
                    <select name="part" class="{{ $select }} mt-1 w-full font-normal normal-case tracking-normal text-gray-900">
                        <option value="">Any part</option>
                        @foreach (App\Models\Plant::PLANT_PARTS as $part)
                            <option value="{{ $part }}" @selected(($filters['part'] ?? '') === $part)>{{ ucfirst($part) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold uppercase tracking-wider text-forest">Toxins
                    <select name="toxins" class="{{ $select }} mt-1 w-full font-normal normal-case tracking-normal text-gray-900">
                        <option value="">Any</option>
                        <option value="documented" @selected(($filters['toxins'] ?? '') === 'documented')>Hazards documented</option>
                        <option value="none" @selected(($filters['toxins'] ?? '') === 'none')>None documented</option>
                    </select>
                </label>
                <label class="text-xs font-semibold uppercase tracking-wider text-forest">Conservation
                    <select name="conservation" class="{{ $select }} mt-1 w-full font-normal normal-case tracking-normal text-gray-900">
                        <option value="">Any status</option>
                        @foreach (App\Models\Plant::CONSERVATION_STATUSES as $code => $label)
                            <option value="{{ $code }}" @selected(($filters['conservation'] ?? '') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            @if ($search || $filters)
                <a href="{{ route('plants.index') }}" class="inline-block text-sm font-medium text-forest hover:underline">Clear search and filters</a>
            @endif
        </form>

        @if ($plants->isEmpty())
            <p class="mt-16 text-center text-lg text-gray-700">No plants match your search.</p>
        @else
            <div class="mt-6 grid gap-4 sm:mt-10 sm:grid-cols-2 sm:gap-8 lg:grid-cols-3">
                @foreach ($plants as $plant)
                    <a href="{{ route('plants.show', $plant) }}" class="group relative flex overflow-hidden rounded-xl bg-sand shadow-sm ring-1 ring-forest/10 transition active:bg-leaf/20 sm:block sm:hover:-translate-y-1 sm:hover:shadow-lg">
                        <x-plant-image :plant="$plant" class="w-28 shrink-0 sm:h-40 sm:w-auto" />
                        @if (in_array($plant->id, $favoriteIds, true))
                            <span class="absolute right-3 top-3 rounded-full bg-cream/90 px-2 py-1 text-xs font-medium text-forest">&#9733; Saved</span>
                        @endif
                        <div class="min-w-0 flex-1 p-4 sm:p-6">
                            <h2 class="truncate font-heading text-lg font-semibold group-hover:text-forest sm:text-2xl">{{ $plant->common_name }}</h2>
                            <p class="truncate text-sm italic text-gray-700 sm:text-base">{{ $plant->scientific_name }}</p>
                            @if ($plant->family)
                                <p class="text-sm text-gray-600">{{ $plant->family }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 sm:mt-4">
                                <span class="badge">{{ $plant->category }}</span>
                                <span class="text-xs text-gray-600">
                                    @if ($plant->sightings_count)
                                        {{ $plant->sightings_count }} trusted {{ Str::plural('sighting', $plant->sightings_count) }} &middot;
                                    @endif
                                    {{ $plant->source_label }}
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">{{ $plants->links() }}</div>
        @endif
    </section>
</x-site-layout>
