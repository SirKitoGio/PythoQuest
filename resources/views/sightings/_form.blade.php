@php($field = 'mt-1 block w-full rounded-lg border-forest/20 bg-sand/60 shadow-sm focus:border-forest focus:ring-leaf')

@csrf

<div x-data="{
        locating: false,
        gpsError: '',
        preview: @js($sighting->photo_path ? Storage::url($sighting->photo_path) : null),
        locate() {
            if (! navigator.geolocation) { this.gpsError = 'This browser does not support GPS.'; return; }
            this.locating = true;
            this.gpsError = '';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.$refs.lat.value = pos.coords.latitude.toFixed(7);
                    this.$refs.lng.value = pos.coords.longitude.toFixed(7);
                    this.locating = false;
                },
                (err) => { this.gpsError = err.message; this.locating = false; },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        },
    }" class="grid gap-8 lg:grid-cols-[320px_1fr]">

    <div>
        <x-input-label for="photo" value="Photo evidence" />
        <label for="photo" class="mt-1 flex h-72 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-forest/30 bg-sand/60 text-center hover:bg-sand">
            <template x-if="preview">
                <img :src="preview" alt="Selected photo" class="h-full w-full object-cover">
            </template>
            <template x-if="! preview">
                <span class="px-6 text-sm text-gray-700">
                    <svg class="mx-auto mb-3 h-12 w-12 text-forest" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8a2 2 0 012-2h2l2-2h6l2 2h2a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>
                    Tap to take or upload a photo<br><span class="text-xs text-gray-500">JPG or PNG, up to 4 MB</span>
                </span>
            </template>
        </label>
        <input id="photo" name="photo" type="file" accept="image/*" capture="environment" class="sr-only"
            @change="const f = $event.target.files[0]; preview = f ? URL.createObjectURL(f) : preview">
        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-input-label for="plant_id" value="Plant" />
            <select id="plant_id" name="plant_id" class="{{ $field }}" required>
                <option value="">Choose the plant you observed</option>
                @foreach ($plants as $plant)
                    <option value="{{ $plant->id }}" @selected((int) old('plant_id', $sighting->plant_id) === $plant->id)>
                        {{ $plant->common_name }} ({{ $plant->scientific_name }})
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('plant_id')" class="mt-2" />
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
            <div class="mt-1 grid grid-cols-2 gap-3">
                <x-text-input x-ref="lat" id="latitude" name="latitude" type="number" step="any" placeholder="Latitude" aria-label="Latitude"
                    :value="old('latitude', $sighting->latitude)" required />
                <x-text-input x-ref="lng" id="longitude" name="longitude" type="number" step="any" placeholder="Longitude" aria-label="Longitude"
                    :value="old('longitude', $sighting->longitude)" required />
            </div>
            <p class="mt-1 text-xs text-gray-600">Confirm or adjust the coordinates before submitting.</p>
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

        <div class="flex items-center gap-3 sm:col-span-2">
            <x-primary-button>{{ $submit }}</x-primary-button>
            <a href="{{ $sighting->exists ? route('sightings.show', $sighting) : route('sightings.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </div>
</div>
