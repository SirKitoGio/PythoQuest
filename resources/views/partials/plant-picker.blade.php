{{-- Searchable picker over the full catalogue via the public API. Expects x-data="plantPicker(initial)". --}}
<div class="relative">
    <label class="text-xs font-semibold uppercase tracking-wider text-forest" :for="$id('plant-search')">Search the catalogue</label>
    <input type="search" :id="$id('plant-search')" x-model="query" @input.debounce.300ms="search()" placeholder="Common or scientific name"
        class="mt-1 block w-full rounded-lg border-forest/20 bg-white/70 text-sm focus:border-forest focus:ring-leaf">
    <ul x-show="results.length" x-cloak class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white text-sm shadow-lg ring-1 ring-forest/15">
        <template x-for="plant in results" :key="plant.id">
            <li><button type="button" @click="choose(plant)" class="block w-full px-3 py-2 text-left hover:bg-sand">
                <span x-text="plant.common_name"></span> <span class="italic text-gray-600" x-text="`(${plant.scientific_name})`"></span>
            </button></li>
        </template>
    </ul>
    <p x-show="selected" class="mt-2 text-sm">Selected: <strong x-text="selected?.label"></strong></p>
    @if ($withHiddenInput ?? true)
        <input type="hidden" name="plant_id" :value="selected?.id">
    @endif
</div>
