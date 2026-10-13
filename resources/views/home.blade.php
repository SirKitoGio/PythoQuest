<x-site-layout title="Home">
    {{-- Hero --}}
    <section class="relative -mt-[104px] bg-forest pb-24 pt-[160px] text-cream">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-[1fr_437px]">
            <div>
                <p class="font-medium text-leaf">A location-aware botanical research companion.</p>
                <h1 class="mt-3 font-heading text-4xl font-bold sm:text-5xl leading-tight sm:text-6xl">
                    Find Wonder
                    <span class="block font-accent italic text-leaf">Where You Stand</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg text-cream/85">
                    Search medicinal plants, log GPS-confirmed sightings and move from field discovery to a traceable, source-backed plant profile.
                </p>

                <form action="{{ route('plants.index') }}" method="GET" class="mt-10 flex max-w-xl items-center overflow-hidden rounded-xl bg-cream shadow-lg">
                    <svg class="ms-6 h-6 w-6 shrink-0 text-forest" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
                    <label for="hero-search" class="sr-only">Search plants</label>
                    <input id="hero-search" type="search" name="q" placeholder="Search by common or scientific name"
                        class="h-16 w-full border-0 bg-transparent px-4 text-lg text-gray-900 placeholder:text-gray-500 focus:ring-0">
                    <button class="me-2 rounded-lg bg-forest px-5 py-3 font-medium text-cream hover:bg-forest-dark">Search</button>
                </form>
                <p class="mt-6 text-sm text-cream/70">Research support only. No diagnosis, dosage or consumption advice.</p>
            </div>

            <x-plant-image :plant="$suggestions->first()" class="hidden h-[479px] rounded-xl shadow-2xl ring-8 ring-leaf/30 lg:block" />
        </div>
    </section>

    {{-- Scanner promo --}}
    <section class="mx-auto mt-24 grid max-w-6xl items-center gap-12 px-6 md:grid-cols-[481px_1fr]">
        <div class="relative flex h-[345px] items-center justify-center overflow-hidden rounded-xl bg-sand shadow-sm ring-1 ring-forest/10">
            <svg class="h-24 w-24 text-forest" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8V5a2 2 0 012-2h3M16 3h3a2 2 0 012 2v3M21 16v3a2 2 0 01-2 2h-3M8 21H5a2 2 0 01-2-2v-3"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16c0-5 3-8 8-8 0 5-3 8-8 8zm0 0c2-2 4-4 6-6"/>
            </svg>
            <span class="absolute bottom-5 left-5 rounded-md bg-forest px-3 py-1 text-xs font-medium text-cream">GPS + photo evidence</span>
        </div>

        <div>
            <p class="text-xl font-medium text-forest">See something growing?</p>
            <h2 class="mt-2 font-heading text-4xl font-semibold">Let's identify it.</h2>
            <p class="mt-5 max-w-lg text-lg text-gray-700">
                Snap a photo, tag the plant part, confirm your location pin and match it to a plant profile. Every sighting stays pending until an expert reviews it.
            </p>
            <a href="{{ route('identifications.create') }}" class="btn-primary mt-8 h-[54px] w-[295px] justify-between px-8 text-lg">
                Open Scanner
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>
    </section>

    {{-- Today's Plant Suggestions --}}
    <section id="suggestions" class="mx-auto mt-32 max-w-6xl px-6">
        <p class="text-xl font-medium text-forest">Curated for you</p>
        <h2 class="mt-1 font-heading text-4xl font-semibold">Today's Plant Suggestions</h2>

        <div class="mt-10 grid gap-12 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($suggestions as $plant)
                <article class="relative rounded-xl bg-sand pb-8 shadow-sm ring-1 ring-forest/10 transition hover:-translate-y-1 hover:shadow-lg">
                    <x-plant-image :plant="$plant" class="h-[219px] rounded-t-xl" />
                    <span class="absolute right-4 top-[180px] flex h-[75px] w-[82px] items-center justify-center rounded-full bg-cream font-heading text-2xl font-bold text-forest shadow">
                        {{ mb_substr($plant->common_name, 0, 1) }}
                    </span>

                    <div class="px-7 pt-5">
                        <h3 class="font-heading text-3xl font-semibold">{{ $plant->common_name }}</h3>
                        <p class="mt-1 text-lg font-medium italic text-gray-700">{{ $plant->scientific_name }}</p>
                        <span class="badge mt-4">{{ $plant->category }}</span>
                        <a href="{{ route('plants.show', $plant) }}" class="mt-10 flex items-center gap-2 text-sm font-medium text-forest hover:underline">
                            Learn more about {{ $plant->common_name }}
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Browse by Category --}}
    @php
        $categoryBlurbs = [
            'Medicinal' => 'Plants with documented extract research.',
            'Herbal' => 'Aromatic and culinary herbs.',
            'Shrubs' => 'Woody, multi-stemmed field finds.',
            'Trees' => 'Bark, leaf and fruit sources.',
        ];
    @endphp
    <section id="categories" class="mx-auto mt-32 grid max-w-6xl items-center gap-10 px-6 lg:grid-cols-[392px_1fr]">
        <div>
            <p class="text-xl font-medium text-forest">Knowledge grows here</p>
            <h2 class="mt-1 font-heading text-4xl font-semibold">Browse by Category</h2>
            <p class="mt-5 text-lg text-gray-700">Jump into plant profiles grouped by growth form and research use, each with habitat, toxin and extract records.</p>
            <a href="{{ route('plants.index') }}" class="btn-secondary mt-6 h-[39px] w-[252px] justify-between">
                Browse more
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>

        <div class="flex snap-x gap-6 overflow-x-auto pb-4">
            @foreach ($categories as $category => $count)
                <a href="{{ route('plants.index', ['category' => $category]) }}"
                    class="group relative flex h-[285px] w-[267px] shrink-0 snap-start flex-col justify-end overflow-hidden rounded-xl bg-gradient-to-br from-leaf to-forest p-7 text-cream shadow-sm transition hover:shadow-xl">
                    <svg class="absolute right-4 top-4 h-20 w-20 text-cream/25 transition group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21c0-9 5-15 16-16-1 11-7 16-16 16z"/></svg>
                    <h3 class="font-heading text-2xl font-semibold">{{ $category }}</h3>
                    <p class="mt-1 text-sm text-cream/85">{{ $categoryBlurbs[$category] }}</p>
                    <p class="mt-3 text-xs font-medium uppercase tracking-wider text-cream/70">{{ $count }} {{ Str::plural('profile', $count) }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Recently Explored --}}
    <section id="recent" class="mx-auto mt-32 max-w-6xl px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xl font-medium text-forest">Your trail of discoveries</p>
                <h2 class="mt-1 font-heading text-4xl font-semibold">Recently Explored</h2>
            </div>
            <p class="max-w-sm text-right text-lg text-gray-700">Every sighting you log becomes part of your research history.</p>
        </div>

        <div class="mt-8 rounded-xl bg-sand px-6 py-6 shadow-sm ring-1 ring-forest/10 sm:px-14">
            <p class="text-xl font-medium">Plant Discovery History</p>
            <div class="mt-4 border-t border-gray-900"></div>

            @auth
                @if ($recent->isEmpty())
                    <p class="py-10 text-center text-gray-700">No sightings yet. <a href="{{ route('sightings.create') }}" class="font-semibold text-forest hover:underline">Log your first one.</a></p>
                @else
                    <div class="grid divide-y divide-gray-900/60 md:grid-cols-3 md:divide-x md:divide-y-0">
                        @foreach ($recent as $sighting)
                            <a href="{{ route('sightings.show', $sighting) }}" class="flex gap-4 py-5 hover:bg-cream/60 md:px-6 md:first:ps-0">
                                <span class="flex h-[43px] w-[45px] shrink-0 items-center justify-center rounded-full bg-forest font-heading font-bold text-cream">
                                    {{ mb_substr($sighting->plant->common_name, 0, 1) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="font-semibold text-lg">{{ $sighting->plant->common_name }}</p>
                                        <p class="text-[11px] font-light italic">{{ $sighting->created_at->isToday() ? 'Today' : ($sighting->created_at->isYesterday() ? 'Yesterday' : $sighting->created_at->diffForHumans()) }}</p>
                                    </div>
                                    <p class="mt-1 line-clamp-3 text-[15px] text-gray-700">{{ $sighting->notes ?? 'Observed '.$sighting->plant_part.' evidence.' }}</p>
                                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-gray-700">
                                        <span class="flex items-center gap-1">
                                            <svg class="h-4 w-4 text-forest" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                                            {{ $sighting->created_at->format('g:i A') }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="h-4 w-4 text-forest" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11a7 7 0 1114 0c0 4.8-7 11-7 11z"/></svg>
                                            {{ $sighting->location_name ?? 'Pinned location' }}
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            @else
                <p class="py-10 text-center text-gray-700">
                    <a href="{{ route('login') }}" class="font-semibold text-forest hover:underline">Log in</a> to see the plants you've recently explored.
                </p>
            @endauth
        </div>
    </section>

    <p class="mx-auto mt-24 max-w-xl px-6 text-center font-heading text-2xl font-semibold">
        Keep discovering! <span class="font-accent italic text-forest">Every leaf has a story.</span>
    </p>
</x-site-layout>
