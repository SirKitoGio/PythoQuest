@php($field = 'mt-1 block w-full rounded-lg border-forest/20 bg-sand/60 shadow-sm focus:border-forest focus:ring-leaf')

@csrf

<div x-data="{
        locating: false,
        gpsError: '',
        accuracy: null,
        pin: null,
        preview: @js($sighting->photoUrl()),
        init() {
            const lat = parseFloat(this.$refs.lat.value);
            const lng = parseFloat(this.$refs.lng.value);
            this.pin = pinPicker(this.$refs.pinMap, {
                latitude: Number.isFinite(lat) ? lat : null,
                longitude: Number.isFinite(lng) ? lng : null,
                onMove: (latitude, longitude) => { this.$refs.lat.value = latitude; this.$refs.lng.value = longitude; this.accuracy = null; this.gpsError = ''; },
            });
            if (! Number.isFinite(lat)) { this.locate(); }
        },
        syncPin() {
            const lat = parseFloat(this.$refs.lat.value);
            const lng = parseFloat(this.$refs.lng.value);
            if (Number.isFinite(lat) && Number.isFinite(lng)) { this.pin.setPosition(lat, lng); }
        },
        locate() {
            if (! navigator.geolocation) { this.gpsError = 'This browser does not support GPS.'; return; }
            this.locating = true;
            this.gpsError = '';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.$refs.lat.value = pos.coords.latitude.toFixed(7);
                    this.$refs.lng.value = pos.coords.longitude.toFixed(7);
                    this.accuracy = Math.round(pos.coords.accuracy);
                    this.pin.setPosition(pos.coords.latitude, pos.coords.longitude);
                    this.locating = false;
                },
                (err) => {
                    this.gpsError = err.code === err.PERMISSION_DENIED
                        ? 'Location access is off. Tap the map to place the pin, or allow location in your browser settings.'
                        : 'Could not get a GPS fix. Move into the open and try again, or tap the map to place the pin.';
                    this.locating = false;
                },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        },
    }" class="grid gap-6 sm:gap-8 lg:grid-cols-[320px_1fr]">

    <div>
        <x-input-label for="photo" value="Photo evidence" />
        <label for="photo" class="mt-1 flex h-56 cursor-pointer sm:h-72 flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-forest/30 bg-sand/60 text-center hover:bg-sand">
            <template x-if="preview">
                <img :src="preview" alt="Selected photo" class="h-full w-full object-cover">
            </template>
            <template x-if="! preview">
                <span class="px-6 text-sm text-gray-700">
                    <svg class="mx-auto mb-3 h-12 w-12 text-forest" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8a2 2 0 012-2h2l2-2h6l2 2h2a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>
                    Tap to take or upload a photo<br><span class="text-xs text-gray-500">JPG, PNG or WebP, up to 8 MB</span>
                </span>
            </template>
        </label>
        <input id="photo" name="photo" type="file" accept="image/*" capture="environment" class="sr-only"
            @change="const f = $event.target.files[0]; preview = f ? URL.createObjectURL(f) : preview">
        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
        <x-input-error :messages="$errors->get('identification_id')" class="mt-2" />
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="sm:col-span-2" x-data="plantPicker(null)"
            @plant-chosen="const option = new Option($event.detail.label, $event.detail.id, true, true); $refs.plantSelect.add(option)">
            <x-input-label for="plant_id" value="Plant" />
            <select id="plant_id" name="plant_id" x-ref="plantSelect" class="{{ $field }}" required>
                <option value="">Choose the plant you observed</option>
                @foreach ($plants as $plant)
                    <option value="{{ $plant->id }}" @selected((int) old('plant_id', $sighting->plant_id) === $plant->id)>
                        {{ $plant->common_name }} ({{ $plant->scientific_name }})
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('plant_id')" class="mt-2" />
            <details class="mt-2 text-sm">
                <summary class="cursor-pointer font-medium text-forest">Not listed? Search the full catalogue</summary>
                <div class="mt-2">@include('partials.plant-picker', ['withHiddenInput' => false])</div>
            </details>
            @if ($sighting->identification_id)
                <input type="hidden" name="identification_id" value="{{ $sighting->identification_id }}">
                <p class="mt-2 text-xs text-gray-600">Linked to scan #{{ $sighting->identification_id }}; its photo is used as evidence unless you upload another.</p>
            @endif
        </div>

        <div>
            <x-input-label for="plant_part" value="Plant part shown" />
            <select id="plant_part" name="plant_part" class="{{ $field }}" required>
                @foreach (\App\Models\Plant::PLANT_PARTS as $option)
                    <option value="{{ $option }}" @selected(old('plant_part', $sighting->plant_part) === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('plant_part')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="observed_on" value="Date observed" />
            <x-text-input id="observed_on" name="observed_on" type="date" class="mt-1 block w-full" :max="now()->toDateString()"
                :value="old('observed_on', $sighting->observed_on?->toDateString())" required />
            <x-input-error :messages="$errors->get('observed_on')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <div class="flex items-end justify-between gap-4">
                <x-input-label value="Location pin" />
                <button type="button" @click="locate()" class="btn-secondary py-1.5 text-xs" :disabled="locating">
                    <span x-text="locating ? 'Locating…' : 'Use my location'"></span>
                </button>
            </div>
            <div x-ref="pinMap" class="isolate mt-2 h-60 overflow-hidden rounded-xl ring-1 ring-forest/15 sm:h-72"></div>
            <p class="mt-2 text-xs text-gray-600">
                Drag the pin or tap the map to confirm the exact spot.
                <span x-show="accuracy" x-text="`GPS accuracy ±${accuracy} m.`"></span>
            </p>
            <div class="mt-2 grid grid-cols-2 gap-3">
                <x-text-input x-ref="lat" id="latitude" name="latitude" type="number" step="any" inputmode="decimal" placeholder="Latitude" aria-label="Latitude"
                    :value="old('latitude', $sighting->latitude)" @change="syncPin()" required />
                <x-text-input x-ref="lng" id="longitude" name="longitude" type="number" step="any" inputmode="decimal" placeholder="Longitude" aria-label="Longitude"
                    :value="old('longitude', $sighting->longitude)" @change="syncPin()" required />
            </div>
            <p x-show="gpsError" x-text="gpsError" class="mt-1 text-sm text-red-600"></p>
            <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
            <x-input-error :messages="$errors->get('longitude')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="location_name" value="Location name" />
            <x-text-input id="location_name" name="location_name" class="mt-1 block w-full" placeholder="e.g. JRU Main Quadrangle"
                :value="old('location_name', $sighting->location_name)" />
            <x-input-error :messages="$errors->get('location_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="visibility" value="Location status" />
            <select id="visibility" name="visibility" class="{{ $field }}" required>
                @foreach (\App\Models\Sighting::VISIBILITIES as $option)
                    <option value="{{ $option }}" @selected(old('visibility', $sighting->visibility) === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="notes" value="Notes (optional)" />
            <textarea id="notes" name="notes" rows="3" class="{{ $field }}">{{ old('notes', $sighting->notes) }}</textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </div>

        <div class="grid grid-cols-2 gap-3 sm:col-span-2 sm:flex sm:items-center">
            <x-primary-button class="min-h-11 justify-center">{{ $submit }}</x-primary-button>
            <a href="{{ $sighting->exists ? route('sightings.show', $sighting) : route('sightings.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </div>
</div>
