<x-app-layout>
    <x-slot name="header">
        <p class="font-medium text-forest">Administration</p>
        <h2 class="font-heading text-3xl font-bold sm:text-4xl">Audit <span class="font-accent italic text-forest">log</span></h2>
        <p class="mt-2 text-gray-700">Every moderation decision, profile change and role change, newest first.</p>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="card overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-forest/20 text-xs uppercase tracking-wider text-gray-600">
                    <tr><th class="py-3 pe-4">When</th><th class="py-3 pe-4">Who</th><th class="py-3 pe-4">Action</th><th class="py-3 pe-4">Record</th><th class="py-3">Changes</th></tr>
                </thead>
                <tbody class="divide-y divide-forest/10 align-top">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap py-3 pe-4 text-gray-600" title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</td>
                            <td class="py-3 pe-4">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="py-3 pe-4 font-mono text-xs">{{ $log->action }}</td>
                            <td class="py-3 pe-4">{{ class_basename($log->auditable_type ?? '') }} #{{ $log->auditable_id }}</td>
                            <td class="py-3 font-mono text-xs text-gray-700">{{ Str::limit(json_encode($log->changes, JSON_UNESCAPED_UNICODE), 160) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-gray-700">No audited actions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
