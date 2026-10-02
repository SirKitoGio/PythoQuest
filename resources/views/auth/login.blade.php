<x-guest-layout>
    <x-slot name="aside">
        <div class="rounded-2xl bg-cream/10 p-8 ring-1 ring-cream/20 sm:p-12">
            <h1 class="font-heading text-4xl font-bold">PhytoQuest</h1>
            <p class="mt-4 text-lg font-medium text-cream/85">
                Pick up where you left off: search plant profiles, revisit your sightings and keep building your field research trail.
            </p>
        </div>
    </x-slot>

    <h2 class="text-center font-heading text-4xl font-bold text-gray-900">Welcome Back!</h2>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-10 space-y-6">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-base" />
            <x-text-input id="email" class="mt-2 block h-14 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" class="text-base" />
            <x-text-input id="password" class="mt-2 block h-14 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between text-sm">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-forest/30 text-forest shadow-sm focus:ring-leaf" name="remember">
                <span class="ms-2 text-gray-700">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="font-medium text-forest underline-offset-4 hover:underline" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <div class="flex flex-col items-center gap-4 pt-2">
            <x-primary-button class="h-14 w-64 !text-base">{{ __('Login') }}</x-primary-button>
            <p class="text-sm text-gray-700">
                New to PhytoQuest?
                <a href="{{ route('register') }}" class="font-semibold text-forest underline-offset-4 hover:underline">Create an account</a>
            </p>
        </div>
    </form>

    <div class="mt-10 border-t border-gray-900/80"></div>
    <p class="mt-8 text-center font-semibold text-gray-900">Or login with</p>

    <div class="mt-6 flex justify-center gap-10">
        @foreach (['Google' => 'G', 'Facebook' => 'f'] as $provider => $glyph)
            <button type="button" disabled title="{{ $provider }} login is not available yet"
                class="flex h-[60px] w-[63px] cursor-not-allowed items-center justify-center rounded-full bg-forest/20 font-heading text-2xl font-bold text-forest/60">
                {{ $glyph }}
                <span class="sr-only">{{ $provider }} (not available)</span>
            </button>
        @endforeach
    </div>
</x-guest-layout>
