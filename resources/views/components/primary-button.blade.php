<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-forest border border-transparent rounded-lg font-medium text-sm text-cream hover:bg-forest-dark focus:outline-none focus:ring-2 focus:ring-leaf focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
