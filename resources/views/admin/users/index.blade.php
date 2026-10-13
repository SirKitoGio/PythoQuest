<x-app-layout>
    <x-slot name="header">
        <p class="font-medium text-forest">Administration</p>
        <h2 class="font-heading text-3xl font-bold sm:text-4xl">Users &amp; <span class="font-accent italic text-forest">roles</span></h2>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <form method="GET" class="flex gap-3">
            <label for="q" class="sr-only">Search users</label>
            <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Name or email" class="h-11 flex-1 rounded-lg border-forest/20 bg-sand/60 focus:border-forest focus:ring-leaf">
            <button class="btn-primary">Search</button>
        </form>

        <div class="card mt-6 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-forest/20 text-xs uppercase tracking-wider text-gray-600">
                    <tr><th class="py-3 pe-4">User</th><th class="py-3 pe-4">Sightings</th><th class="py-3 pe-4">Scans</th><th class="py-3">Role</th></tr>
                </thead>
                <tbody class="divide-y divide-forest/10">
                    @foreach ($users as $user)
                        <tr>
                            <td class="py-3 pe-4"><p class="font-medium">{{ $user->name }}</p><p class="text-gray-600">{{ $user->email }}</p></td>
                            <td class="py-3 pe-4">{{ $user->sightings_count }}</td>
                            <td class="py-3 pe-4">{{ $user->identifications_count }}</td>
                            <td class="py-3">
                                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <label for="role-{{ $user->id }}" class="sr-only">Role</label>
                                    <select id="role-{{ $user->id }}" name="role" class="h-9 rounded-lg border-forest/20 bg-sand/60 text-sm focus:border-forest focus:ring-leaf" @disabled($user->is(Auth::user()))>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                    @unless ($user->is(Auth::user()))
                                        <button class="btn-secondary px-3 py-1.5 text-xs">Save</button>
                                    @endunless
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $users->links() }}</div>
    </div>
</x-app-layout>
