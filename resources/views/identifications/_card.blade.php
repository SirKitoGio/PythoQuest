<a href="{{ route('identifications.show', $identification) }}" class="group overflow-hidden rounded-xl bg-sand shadow-sm ring-1 ring-forest/10 transition hover:-translate-y-1">
    <img src="{{ $identification->imageUrl() }}" alt="" class="h-36 w-full object-cover">
    <div class="p-4 text-sm">
        <p class="font-medium group-hover:text-forest">
            {{ $identification->status === 'completed' ? ($identification->topPlant?->common_name ?? 'No catalogue match') : ucfirst($identification->status) }}
        </p>
        <p class="text-gray-600">
            {{ ucfirst($identification->plant_part) }}
            @if ($identification->top_confidence !== null)
                &middot; {{ number_format($identification->top_confidence * 100) }}%
            @endif
            &middot; {{ $identification->created_at->diffForHumans() }}
        </p>
    </div>
</a>
