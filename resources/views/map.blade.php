<x-site-layout title="Sighting map">
    <section class="mx-auto max-w-6xl px-4 pt-8 sm:px-6 sm:pt-14"
        x-data="{ plant: @js(request()->integer('plant') ?: ''), trustedOnly: false, count: null, map: null,
            async refresh() { this.count = await this.map.load({ plant_id: this.plant, status: this.trustedOnly ? 'trusted' : '' }) } }"
        x-init="map = sightingMap($refs.map, { sourceUrl: @js(route('api.v1.map.sightings')) }); refresh()">
        <p class="text-xl font-medium text-forest">Field records</p>
        <h1 class="mt-1 font-heading text-4xl font-bold sm:text-5xl">Sighting <span class="font-accent italic text-forest">map</span></h1>
        <p class="mt-3 max-w-3xl text-gray-700">
            Solid pins are trusted records of common plants in public areas. Shaded squares are generalized to a ~1 km area because the record is unverified, protected, or of a sensitive species. Private locations never appear.
        </p>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
            <label for="plant" class="sr-only">Plant</label>
            <select id="plant" x-model="plant" @change="refresh()" class="h-11 rounded-lg border-forest/20 bg-sand/60 text-sm focus:border-forest focus:ring-leaf sm:w-72">
                <option value="">All observed plants</option>
                @foreach ($plants as $plant)
                    <option value="{{ $plant->id }}">{{ $plant->common_name }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm text-gray-800">
                <input type="checkbox" x-model="trustedOnly" @change="refresh()" class="rounded border-forest/30 text-forest focus:ring-leaf">
                Trusted records only
            </label>
            <span class="text-sm text-gray-600 sm:ms-auto" x-show="count !== null" x-text="`${count} records shown`"></span>
        </div>

        <div x-ref="map" class="isolate mt-4 h-[62vh] overflow-hidden sm:mt-6 sm:h-[560px] rounded-xl shadow-sm ring-1 ring-forest/10"></div>

        <div class="mt-4 flex flex-wrap gap-5 text-sm text-gray-700">
            <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-forest"></span> Trusted</span>
            <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-amber-700"></span> Pending review</span>
            <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-orange-700"></span> Needs correction</span>
            <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-purple-700"></span> Sensitive-restricted</span>
        </div>
    </section>
</x-site-layout>
