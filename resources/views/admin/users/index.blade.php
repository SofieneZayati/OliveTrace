@extends('layouts.admin')
@section('title', 'Users')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">The OliveTrace community</p>
            <h1 class="display-title text-4xl sm:text-5xl">People & access.</h1>
            <p class="mt-4 text-sm text-stone-500">Manage account details, roles and access.</p>
        </div>
        <span class="role-badge"><x-icon name="users" class="h-4 w-4" />User management</span>
    </div>
    <x-card>
        <form method="GET" action="{{ route('admin.users.index') }}" class="mb-7 grid gap-4 border-b border-stone-100 pb-7 sm:grid-cols-2 xl:grid-cols-[1.4fr_1fr_1fr_auto]">
            <div>
                <x-input-label for="search" value="Search name or email" />
                <x-text-input id="search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="Find someone..." class="mt-2 w-full" />
            </div>
            <div>
                <x-input-label for="role" value="Role" />
                <select id="role" name="role" class="mt-2 w-full">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="active" value="Account status" />
                <select id="active" name="active" class="mt-2 w-full">
                    <option value="">All accounts</option>
                    <option value="1" @selected(request('active') === '1')>Active</option>
                    <option value="0" @selected(request('active') === '0')>Inactive</option>
                </select>
            </div>
            <div class="flex items-end gap-4">
                <x-primary-button>Filter</x-primary-button>
                <a href="{{ route('admin.users.index') }}" class="text-link py-3 text-stone-500">Reset</a>
            </div>
        </form>
        <div class="relative overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">User accounts</caption>
                <thead class="border-b border-stone-200 text-xs text-stone-500">
                    <tr><th scope="col" class="pb-4 pr-5 font-medium">Name</th><th scope="col" class="pb-4 pr-5 font-medium">Email</th><th scope="col" class="pb-4 pr-5 font-medium">Role</th><th scope="col" class="pb-4 pr-5 font-medium">Status</th><th scope="col" class="pb-4 font-medium">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b border-stone-100 transition-colors hover:bg-olive-50/40">
                            <td class="py-5 pr-5">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-olive-50 text-xs font-semibold text-olive-700" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                    <span class="whitespace-nowrap font-semibold">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="py-5 pr-5 text-stone-500">{{ $user->email }}</td>
                            <td class="py-5 pr-5"><span class="rounded-md bg-stone-100 px-2.5 py-1 text-xs text-stone-600">{{ $user->role->label() }}</span></td>
                            <td class="py-5 pr-5"><span class="inline-flex items-center gap-1.5 whitespace-nowrap text-xs {{ $user->is_active ? 'text-olive-700' : 'text-stone-500' }}"><span class="h-1.5 w-1.5 rounded-full {{ $user->is_active ? 'bg-olive-500' : 'bg-stone-400' }}"></span>{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="whitespace-nowrap py-5"><a href="{{ route('admin.users.show', $user) }}" class="text-link mr-4 text-xs">View<span class="sr-only"> {{ $user->name }}</span></a><a href="{{ route('admin.users.edit', $user) }}" class="text-link text-xs">Edit<span class="sr-only"> {{ $user->name }}</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-12 text-center text-stone-500">No users match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $users->links() }}</div>
    </x-card>
@endsection
