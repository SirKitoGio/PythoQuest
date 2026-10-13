@props(['status'])

<span {{ $attributes->class([
    'inline-block rounded-md px-2.5 py-0.5 text-xs font-medium capitalize',
    'bg-leaf/40 text-forest-dark' => $status === 'trusted',
    'bg-sand text-amber-800 ring-1 ring-amber-700/20' => $status === 'pending',
    'bg-orange-100 text-orange-800' => $status === 'needs correction',
    'bg-red-100 text-red-800' => $status === 'rejected',
    'bg-purple-100 text-purple-800' => $status === 'sensitive-restricted',
]) }}>{{ $status }}</span>
