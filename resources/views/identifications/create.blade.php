<x-app-layout>
    <x-slot name="header">
        <p class="font-medium text-forest">Plant scanner</p>
        <h2 class="font-heading text-3xl font-bold sm:text-4xl">Identify a <span class="font-accent italic text-forest">plant</span></h2>
        <p class="mt-2 max-w-2xl text-sm text-gray-700 sm:text-base">Photograph one plant part. You get ranked candidates with confidence scores — a starting point for research, never a confirmed identification.</p>
    </x-slot>

    <form method="POST" action="{{ route('identifications.store') }}" enctype="multipart/form-data"
        x-data="photoCapture()" @submit="handleSubmit($event)" @keydown.escape.window="stopCamera()"
        class="mx-auto grid max-w-6xl gap-6 px-4 py-6 sm:gap-8 sm:px-6 sm:py-8 lg:grid-cols-[380px_1fr]">
        @csrf

        <div>
            {{-- In-browser live camera viewfinder (works on mobile phones and desktop with back/front camera toggle) --}}
            <div x-show="streaming" x-cloak class="relative h-72 overflow-hidden rounded-xl bg-black sm:h-80 shadow-md">
                <video x-ref="video" autoplay playsinline muted class="h-full w-full object-cover"></video>

                {{-- Targeting reticle overlay --}}
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center p-6">
                    <p class="mb-2 rounded bg-black/50 px-2 py-0.5 text-xs font-medium text-white/90 backdrop-blur-xs">Center leaf, flower, or bark</p>
                    <div class="relative h-40 w-40 rounded-xl border border-white/40 sm:h-52 sm:w-52">
                        <div class="absolute -top-1 -left-1 h-4 w-4 border-t-2 border-l-2 border-emerald-400"></div>
                        <div class="absolute -top-1 -right-1 h-4 w-4 border-t-2 border-r-2 border-emerald-400"></div>
                        <div class="absolute -bottom-1 -left-1 h-4 w-4 border-b-2 border-l-2 border-emerald-400"></div>
                        <div class="absolute -bottom-1 -right-1 h-4 w-4 border-b-2 border-r-2 border-emerald-400"></div>
                    </div>
                </div>

                <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/80 via-black/40 to-transparent p-3">
                    <button type="button" @click="stopCamera()" class="min-h-11 rounded-lg bg-white/20 px-3 text-sm font-medium text-white backdrop-blur-xs hover:bg-white/30">Cancel</button>
                    <button type="button" @click="snap()" class="relative flex h-16 w-16 items-center justify-center rounded-full border-4 border-white bg-white/30 backdrop-blur-xs transition-transform active:scale-95" aria-label="Capture photo">
                        <span class="h-10 w-10 rounded-full bg-white shadow-md"></span>
                    </button>
                    <button type="button" @click="switchCamera()" class="flex min-h-11 items-center gap-1.5 rounded-lg bg-white/20 px-3 text-sm font-medium text-white backdrop-blur-xs hover:bg-white/30" aria-label="Switch camera">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Flip
                    </button>
                </div>
            </div>

            {{-- Photo preview or empty state placeholder --}}
            <div x-show="! streaming" class="flex h-64 flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-forest/30 bg-sand/60 text-center sm:h-80 relative group">
                <template x-if="preview">
                    <img :src="preview" alt="Selected photo" class="h-full w-full object-cover">
                </template>
                <template x-if="! preview">
                    <span class="px-6 text-sm text-gray-700">
                        <svg class="mx-auto mb-3 h-12 w-12 text-forest" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8a2 2 0 012-2h2l2-2h6l2 2h2a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>
                        Take a photo or upload one<br><span class="text-xs text-gray-500">JPG, PNG or WebP, up to 8 MB</span>
                    </span>
                </template>
            </div>

            {{-- Action buttons --}}
            <div x-show="! streaming" class="mt-3 grid grid-cols-2 gap-2 sm:gap-3">
                <button type="button" @click="takePhoto()" class="btn-primary flex items-center justify-center gap-1.5 py-2.5">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8a2 2 0 012-2h2l2-2h6l2 2h2a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>
                    <span x-text="preview ? 'Rescan' : 'Live scanner'">Live scanner</span>
                </button>
                <button type="button" @click="chooseFromGallery()" class="btn-secondary flex items-center justify-center gap-1.5 py-2.5">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 16l5-5 4 4 3-3 6 6"/><circle cx="15.5" cy="8.5" r="1.5"/></svg>
                    Upload photo
                </button>
            </div>

            {{-- Optional native camera trigger for devices that prefer native camera app --}}
            <div x-show="! streaming" class="mt-2 text-center">
                <button type="button" @click="openNativeCamera()" class="text-xs text-forest hover:underline">
                    Prefer your device camera app? Tap here
                </button>
            </div>

            <p x-show="cameraError" x-text="cameraError" x-cloak class="mt-2 rounded-lg bg-red-50 p-2 text-sm text-red-700 ring-1 ring-red-200"></p>

            {{-- The named input is what gets submitted; the other two act as pickers --}}
            <input x-ref="image" id="image" name="image" type="file" accept="image/*" class="sr-only" tabindex="-1" required aria-label="Plant photo">
            <input x-ref="cameraInput" type="file" accept="image/*" capture="environment" class="hidden" @change="useFile($event.target.files[0])">
            <input x-ref="galleryInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="useFile($event.target.files[0])">
            <x-input-error :messages="$errors->get('image')" class="mt-2" />
        </div>

        <div class="card bg-sand">
            <fieldset>
                <legend class="font-heading text-lg font-semibold">Which part is in the photo?</legend>
                <div class="mt-4 grid grid-cols-3 gap-2 sm:gap-3">
                    @foreach ($parts as $part)
                        <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg bg-white/70 px-3 py-2 text-sm sm:px-4 sm:py-3 ring-1 ring-forest/15 has-[:checked]:bg-leaf/30 has-[:checked]:ring-forest">
                            <input type="radio" name="plant_part" value="{{ $part }}" @checked(old('plant_part', 'leaf') === $part) class="text-forest focus:ring-leaf">
                            {{ ucfirst($part) }}
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('plant_part')" class="mt-2" />
            </fieldset>

            <ul class="mt-6 hidden space-y-1 text-sm text-gray-700 sm:block">
                <li>&bull; Fill the frame with a single leaf, flower or bark patch.</li>
                <li>&bull; Use daylight and avoid blur; photograph several parts if unsure.</li>
                <li>&bull; Photos are re-encoded and location metadata is removed on upload.</li>
            </ul>

            <button class="btn-primary mt-6 w-full sm:mt-8 sm:w-auto" :disabled="submitting || ! preview">
                <span x-text="submitting ? 'Uploading…' : (preview ? 'Identify plant' : 'Add a photo to continue')"></span>
            </button>
        </div>
    </form>
</x-app-layout>
