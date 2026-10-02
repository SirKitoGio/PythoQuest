<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-5 py-2.5 bg-sand border border-forest/30 rounded-lg font-medium text-sm text-forest hover:bg-leaf/30 focus:outline-none focus:ring-2 focus:ring-leaf focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
