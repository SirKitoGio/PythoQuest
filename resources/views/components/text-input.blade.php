@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-forest/20 bg-sand/60 focus:border-forest focus:ring-leaf rounded-lg shadow-sm']) }}>
