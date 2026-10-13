<x-app-layout>
    <x-slot name="header">
        <p class="font-medium text-forest">Expert review</p>
        <h2 class="font-heading text-3xl font-bold sm:text-4xl">Review <span class="font-accent italic text-forest">queue</span></h2>
        <p class="mt-2 max-w-3xl text-gray-700">Assess identity evidence, location accuracy and sensitivity. Community confirmations are supporting evidence only; your decision sets the record's status and the submitter is notified.</p>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="flex gap-2 border-b border-forest/15">
            @foreach (['sightings' => 'Sightings', 'identifications' => 'Escalated scans'] as $key => $label)
                <a href="{{ route('review.index', ['tab' => $key]) }}" @class([
                    '-mb-px border-b-2 px-4 py-2 text-sm font-medium',
                    'border-forest text-forest' => $tab === $key,
                    'border-transparent text-gray-600 hover:text-forest' => $tab !== $key,
                ])>{{ $label }} <span class="ms-1 rounded-full bg-sand px-2 text-xs">{{ $counts[$key] }}</span></a>
            @endforeach
        </div>

        @if ($tab === 'sightings')
            @forelse ($sightings as $sighting)
                <article class="card mt-6 grid gap-6 bg-sand lg:grid-cols-[220px_1fr_340px]">
                    <x-plant-image :plant="$sighting->plant" :src="$sighting->photoUrl()" class="h-48 rounded-lg" />

                    <div class="text-sm">
                        <h3 class="font-heading text-2xl font-semibold"><a href="{{ route('plants.show', $sighting->plant) }}" class="hover:text-forest">{{ $sighting->plant->common_name }}</a></h3>
                        <p class="italic text-gray-700">{{ $sighting->plant->scientific_name }}</p>
                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1">
                            <dt class="text-gray-600">Submitted by</dt><dd>{{ $sighting->user->name }}</dd>
                            <dt class="text-gray-600">Observed</dt><dd>{{ $sighting->observed_on->format('M j, Y') }}</dd>
                            <dt class="text-gray-600">Part</dt><dd class="capitalize">{{ $sighting->plant_part }}</dd>
                            <dt class="text-gray-600">Location status</dt><dd class="capitalize">{{ $sighting->visibility }}</dd>
                            <dt class="text-gray-600">Coordinates</dt>
                            <dd><a href="https://www.openstreetmap.org/?mlat={{ $sighting->latitude }}&mlon={{ $sighting->longitude }}#map=18/{{ $sighting->latitude }}/{{ $sighting->longitude }}" target="_blank" rel="noopener" class="text-forest hover:underline">{{ number_format($sighting->latitude, 5) }}, {{ number_format($sighting->longitude, 5) }}</a></dd>
                            <dt class="text-gray-600">Confirmations</dt><dd>{{ $sighting->confirmations_count }}</dd>
                            <dt class="text-gray-600">Conservation</dt><dd>{{ App\Models\Plant::CONSERVATION_STATUSES[$sighting->plant->conservation_status] ?? '—' }}</dd>
                            @if ($sighting->identification?->top_confidence !== null)
                                <dt class="text-gray-600">Model match</dt><dd>{{ number_format($sighting->identification->top_confidence * 100) }}%</dd>
                            @endif
                        </dl>
                        @if ($sighting->notes)
                            <p class="mt-3 rounded-lg bg-white/70 p-3 text-gray-800">{{ $sighting->notes }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('review.sightings.store', $sighting) }}" class="space-y-3" x-data="{ decision: 'trusted' }">
                        @csrf
                        <fieldset class="grid grid-cols-2 gap-2 text-sm">
                            <legend class="sr-only">Decision</legend>
                            @foreach (App\Models\Sighting::REVIEW_DECISIONS as $decision)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg bg-white/70 px-3 py-2 capitalize ring-1 ring-forest/15">
                                    <input type="radio" name="decision" value="{{ $decision }}" x-model="decision" class="text-forest focus:ring-leaf">
                                    {{ $decision }}
                                </label>
                            @endforeach
                        </fieldset>
                        <label for="note-{{ $sighting->id }}" class="sr-only">Note to submitter</label>
                        <textarea id="note-{{ $sighting->id }}" name="note" rows="3" placeholder="Note to the submitter (required for corrections and rejections)"
                            :required="['needs correction', 'rejected'].includes(decision)"
                            class="block w-full rounded-lg border-forest/20 bg-white/70 text-sm focus:border-forest focus:ring-leaf"></textarea>
                        <x-input-error :messages="$errors->get('note')" />
                        <button class="btn-primary w-full">Record decision</button>
                    </form>
                </article>
            @empty
                <p class="py-16 text-center text-gray-700">The queue is clear. Nice work.</p>
            @endforelse
            <div class="mt-8">{{ $sightings->appends(['tab' => 'sightings'])->links() }}</div>
        @else
            @forelse ($identifications as $identification)
                <article class="card mt-6 grid gap-6 bg-sand lg:grid-cols-[220px_1fr_340px]">
                    <img src="{{ $identification->imageUrl() }}" alt="" class="h-48 w-full rounded-lg object-cover">
                    <div class="text-sm">
                        <p class="font-medium">{{ $identification->user->name }} &middot; {{ ucfirst($identification->plant_part) }}</p>
                        <p class="text-gray-600">Escalated {{ $identification->escalated_at->diffForHumans() }}</p>
                        <ol class="mt-3 space-y-1">
                            @foreach ($identification->candidates ?? [] as $candidate)
                                <li>{{ $candidate['common_name'] ?? $candidate['scientific_name'] }} <span class="italic text-gray-600">({{ $candidate['scientific_name'] }})</span> — {{ number_format($candidate['score'] * 100, 1) }}%</li>
                            @endforeach
                        </ol>
                    </div>
                    <form method="POST" action="{{ route('review.identifications.store', $identification) }}" class="space-y-3"
                        x-data="plantPicker(@js($identification->topPlant ? ['id' => $identification->top_plant_id, 'label' => $identification->topPlant->common_name.' ('.$identification->topPlant->scientific_name.')'] : null))">
                        @csrf
                        @include('partials.plant-picker')
                        <button class="btn-primary w-full" :disabled="! selected">Confirm species</button>
                    </form>
                </article>
            @empty
                <p class="py-16 text-center text-gray-700">No escalated scans.</p>
            @endforelse
            <div class="mt-8">{{ $identifications->appends(['tab' => 'identifications'])->links() }}</div>
        @endif
    </div>
</x-app-layout>
