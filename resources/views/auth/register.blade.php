<x-guest-layout>
    <x-slot name="aside">
        <h1 class="font-heading text-4xl font-bold sm:text-5xl">Let's Get Started</h1>
        <p class="mt-5 max-w-md text-lg font-medium text-cream/85">
            Join PhytoQuest to log GPS-confirmed sightings, save your plant discoveries and build source-backed field records.
        </p>
    </x-slot>

    <h2 class="text-center font-heading text-4xl font-bold text-gray-900">Create Account</h2>

    <form method="POST" action="{{ route('register') }}" class="mt-10 space-y-6">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Name')" class="text-base" />
            <x-text-input id="name" class="mt-2 block h-14 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-base" />
            <x-text-input id="email" class="mt-2 block h-14 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-base" />
            <x-text-input id="password" class="mt-2 block h-14 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-base" />
            <x-text-input id="password_confirmation" class="mt-2 block h-14 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-col items-center gap-4 pt-4">
            <x-primary-button class="h-14 w-64 !text-base">{{ __('Sign Up') }}</x-primary-button>
            <p class="text-sm text-gray-700">
                Already registered?
                <a href="{{ route('login') }}" class="font-semibold text-forest underline-offset-4 hover:underline">Log in</a>
            </p>
        </div>
    </form>
</x-guest-layout>
