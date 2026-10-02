@php($field = 'mt-1 block w-full rounded-lg border-forest/20 bg-sand/60 shadow-sm focus:border-forest focus:ring-leaf')

@csrf

<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="common_name" value="Common name" />
        <x-text-input id="common_name" name="common_name" class="mt-1 block w-full" :value="old('common_name', $plant->common_name)" required />
        <x-input-error :messages="$errors->get('common_name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="scientific_name" value="Scientific name" />
        <x-text-input id="scientific_name" name="scientific_name" class="mt-1 block w-full italic" :value="old('scientific_name', $plant->scientific_name)" required />
        <x-input-error :messages="$errors->get('scientific_name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="category" value="Category" />
        <select id="category" name="category" class="{{ $field }}" required>
            @foreach (\App\Models\Plant::CATEGORIES as $option)
                <option value="{{ $option }}" @selected(old('category', $plant->category) === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="source_label" value="Source label" />
        <select id="source_label" name="source_label" class="{{ $field }}" required>
            @foreach (\App\Models\Plant::SOURCE_LABELS as $option)
                <option value="{{ $option }}" @selected(old('source_label', $plant->source_label) === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('source_label')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="habitat" value="Habitat" />
        <x-text-input id="habitat" name="habitat" class="mt-1 block w-full" :value="old('habitat', $plant->habitat)" required />
        <x-input-error :messages="$errors->get('habitat')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="plant_parts" value="Plant parts studied" />
        <x-text-input id="plant_parts" name="plant_parts" class="mt-1 block w-full" placeholder="leaf, bark, fruit" :value="old('plant_parts', $plant->plant_parts)" required />
        <x-input-error :messages="$errors->get('plant_parts')" class="mt-2" />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="physical_features" value="Physical features" />
        <textarea id="physical_features" name="physical_features" rows="3" class="{{ $field }}" required>{{ old('physical_features', $plant->physical_features) }}</textarea>
        <x-input-error :messages="$errors->get('physical_features')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="known_toxins" value="Known toxins" />
        <textarea id="known_toxins" name="known_toxins" rows="3" class="{{ $field }}">{{ old('known_toxins', $plant->known_toxins) }}</textarea>
        <x-input-error :messages="$errors->get('known_toxins')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="extract_types" value="Documented extract types" />
        <textarea id="extract_types" name="extract_types" rows="3" class="{{ $field }}">{{ old('extract_types', $plant->extract_types) }}</textarea>
        <x-input-error :messages="$errors->get('extract_types')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="reference" value="Research reference" />
        <x-text-input id="reference" name="reference" class="mt-1 block w-full" :value="old('reference', $plant->reference)" />
        <x-input-error :messages="$errors->get('reference')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="image_url" value="Image URL (optional)" />
        <x-text-input id="image_url" name="image_url" type="url" class="mt-1 block w-full" :value="old('image_url', $plant->image_url)" />
        <x-input-error :messages="$errors->get('image_url')" class="mt-2" />
    </div>
</div>

<div class="mt-8 flex items-center gap-3">
    <x-primary-button>{{ $submit }}</x-primary-button>
    <a href="{{ $plant->exists ? route('plants.show', $plant) : route('plants.index') }}" class="btn-secondary">Cancel</a>
</div>
