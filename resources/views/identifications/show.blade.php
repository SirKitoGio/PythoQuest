<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('identifications.index') }}" class="text-sm font-medium text-forest hover:underline">&larr; My scans</a>
        <h2 class="mt-3 font-heading text-3xl font-bold sm:text-4xl">Identification <span class="font-accent italic text-forest">results</span></h2>
    </x-slot>

    <div class="mx-auto grid max-w-6xl gap-6 px-4 py-6 sm:gap-8 sm:px-6 sm:py-8 lg:grid-cols-[380px_1fr] [&>*]:min-w-0"
        x-data="{
            status: @js($identification->status),
            async poll() {
                const response = await fetch(@js(route('identifications.show', $identification)), { headers: { Accept: 'application/json' } });
                const { data } = await response.json();
                if (data.status !== this.status) { window.location.reload(); }
            },
            init() {
                if (['completed', 'failed'].includes(this.status)) return;
                window.Echo?.private(`App.Models.User.{{ $identification->user_id }}`)
                    .listen('.identification.updated', (event) => { if (event.id === {{ $identification->id }} && ['completed', 'failed'].includes(event.status)) window.location.reload(); });
                const timer = setInterval(() => ['completed', 'failed'].includes(this.status) ? clearInterval(timer) : this.poll(), 4000);
            },
        }">
        <div>
            <img src="{{ $identification->imageUrl() }}" alt="Scanned {{ $identification->plant_part }}" class="h-56 w-full rounded-xl object-cover shadow-sm sm:h-80">
            <p class="mt-2 text-sm text-gray-600">{{ ucfirst($identification->plant_part) }} &middot; {{ $identification->created_at->diffForHumans() }}</p>
        </div>

        <div class="card bg-sand">
            @if (! $identification->isFinished())
                <div class="flex items-center gap-4">
                    <svg class="h-8 w-8 animate-spin text-forest" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <div>
                        <p class="font-heading text-xl font-semibold">{{ $identification->status === 'queued' ? 'Queued for analysis' : 'Analysing image…' }}</p>
                        <p class="text-sm text-gray-700">You can leave this page — results will appear here and in your scan history.</p>
                    </div>
                </div>
            @elseif ($identification->status === 'failed')
                <p class="font-heading text-xl font-semibold text-red-800">Identification failed</p>
                <p class="mt-2 text-gray-700">{{ $identification->error }}</p>
            @else
                @if ($identification->confirmedPlant)
                    <div class="mb-6 rounded-lg bg-leaf/30 px-4 py-3 text-forest-dark">
                        An expert confirmed this as <a href="{{ route('plants.show', $identification->confirmedPlant) }}" class="font-semibold underline">{{ $identification->confirmedPlant->common_name }}</a>
                        (<span class="italic">{{ $identification->confirmedPlant->scientific_name }}</span>).
                    </div>
                @endif

                @if ($identification->needs_more_images)
                    <div class="mb-6 rounded-lg bg-orange-100 px-4 py-3 text-sm text-orange-900">
                        <strong>Low confidence.</strong> Try another photo of a different part (flower or fruit help most), or send this scan to an expert reviewer.
                    </div>
                @endif

                <h3 class="font-heading text-lg font-semibold">Possible matches</h3>
                <ol class="mt-4 space-y-3">
                    @foreach ($identification->candidates as $index => $candidate)
                        <li class="rounded-lg bg-white/70 p-4 ring-1 ring-forest/10">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-medium">{{ $index + 1 }}. {{ $candidate['common_name'] ?? $candidate['scientific_name'] }}</p>
                                    <p class="text-sm italic text-gray-700">{{ $candidate['scientific_name'] }}</p>
                                </div>
                                <span class="font-heading text-lg font-semibold text-forest">{{ number_format($candidate['score'] * 100, 1) }}%</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-sand"><div class="h-full bg-forest" style="width: {{ max(1, $candidate['score'] * 100) }}%"></div></div>
                            @if ($candidate['plant_id'])
                                <div class="mt-3 grid grid-cols-2 gap-2 text-sm sm:flex sm:gap-4">
                                    <a href="{{ route('plants.show', $candidate['plant_id']) }}" class="flex min-h-11 items-center justify-center rounded-lg ring-1 ring-forest/20 font-medium text-forest sm:min-h-0 sm:justify-start sm:ring-0 sm:hover:underline">View profile</a>
                                    <a href="{{ route('sightings.create', ['identification' => $identification->id, 'plant' => $candidate['plant_id']]) }}" class="flex min-h-11 items-center justify-center rounded-lg bg-forest/10 px-2 text-center font-medium text-forest sm:min-h-0 sm:justify-start sm:bg-transparent sm:px-0 sm:hover:underline">Log as this plant</a>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
                <p class="mt-4 text-xs text-gray-600">Scores are relative to the {{ number_format(App\Models\Plant::count()) }} species in the catalogue. Model: {{ $identification->model_version }}@if ($identification->latency_ms) &middot; {{ $identification->latency_ms }} ms @endif</p>
            @endif

            <div class="mt-8 grid gap-3 border-t border-forest/10 pt-6 sm:flex sm:flex-wrap">
                <a href="{{ route('identifications.create') }}" class="btn-secondary">Scan another image</a>
                @can('escalate', $identification)
                    <form method="POST" action="{{ route('identifications.escalation.store', $identification) }}" class="grid">
                        @csrf
                        <button class="btn-primary">Ask an expert</button>
                    </form>
                @endcan
                @if ($identification->escalated_at && ! $identification->reviewed_at)
                    <span class="self-center text-sm text-gray-700">Escalated {{ $identification->escalated_at->diffForHumans() }} — awaiting an expert.</span>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
