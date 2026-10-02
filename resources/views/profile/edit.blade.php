<x-app-layout>
    <x-slot name="header">
        <h2 class="font-heading text-3xl font-bold text-forest">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-6">
            <div class="p-4 sm:p-8 bg-white/70 shadow-sm ring-1 ring-forest/10 sm:rounded-xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white/70 shadow-sm ring-1 ring-forest/10 sm:rounded-xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white/70 shadow-sm ring-1 ring-forest/10 sm:rounded-xl">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
