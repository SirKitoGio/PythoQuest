<figure>
    <x-plant-image :plant="$plant" class="h-56 rounded-xl shadow-sm sm:h-72" />
    @if ($plant->image_url && str_contains($plant->image_url, 'pfaf.org'))
        <figcaption class="mt-1 text-xs text-gray-500">Image: Plants For A Future</figcaption>
    @endif
</figure>

@auth
    <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
        <form method="POST" class="contents" action="{{ $isFavorite ? route('plants.favorite.destroy', $plant) : route('plants.favorite.store', $plant) }}">
            @csrf
            @if ($isFavorite)
                @method('DELETE')
            @endif
            <button class="btn-secondary w-full sm:w-auto">{{ $isFavorite ? '★ Saved' : '☆ Favorite' }}</button>
        </form>
        <button type="button" x-data="offlineSave(@js(route('plants.show', $plant)))" @click="save()" class="btn-secondary" x-text="label">Save for offline</button>
        <a href="{{ route('sightings.create', ['plant' => $plant->id]) }}" class="btn-primary col-span-2">Log a sighting</a>
    </div>
@endauth
