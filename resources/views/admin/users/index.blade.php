@extends('layouts.admin')
@section('title', 'Users')
@section('content')
    <h1 class="mb-2 text-3xl font-bold">Users</h1>
    <p class="mb-6 text-stone-600">Manage account details, roles and access.</p>
    <x-card>
        <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 grid gap-4 sm:grid-cols-4">
            <div>
                <x-input-label for="search" value="Search name or email" />
                <x-text-input id="search" name="search" value="{{ request('search') }}" maxlength="100" class="mt-1 w-full" />
            </div>
            <div>
                <x-input-label for="role" value="Role" />
                <select id="role" name="role" class="mt-1 w-full rounded-md border-stone-300">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="active" value="Account status" />
                <select id="active" name="active" class="mt-1 w-full rounded-md border-stone-300">
                    <option value="">All accounts</option>
                    <option value="1" @selected(request('active') === '1')>Active</option>
                    <option value="0" @selected(request('active') === '0')>Inactive</option>
                </select>
            </div>
            <div class="flex items-end gap-3">
                <x-primary-button>Filter</x-primary-button>
                <a href="{{ route('admin.users.index') }}" class="py-2 text-sm text-stone-600 underline">Reset</a>
            </div>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">User accounts</caption>
                <thead class="border-b border-stone-200 text-stone-500"><tr><th scope="col" class="py-3 pr-4">Name</th><th scope="col" class="py-3 pr-4">Email</th><th scope="col" class="py-3 pr-4">Role</th><th scope="col" class="py-3 pr-4">Status</th><th scope="col" class="py-3">Actions</th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b border-stone-100">
                            <td class="py-4 pr-4 font-medium">{{ $user->name }}</td><td class="py-4 pr-4">{{ $user->email }}</td><td class="py-4 pr-4">{{ $user->role->label() }}</td>
                            <td class="py-4 pr-4"><span class="rounded-full px-2 py-1 text-xs {{ $user->is_active ? 'bg-green-50 text-green-800' : 'bg-stone-100 text-stone-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="whitespace-nowrap py-4"><a href="{{ route('admin.users.show', $user) }}" class="mr-3 text-olive-700 underline">View<span class="sr-only"> {{ $user->name }}</span></a><a href="{{ route('admin.users.edit', $user) }}" class="text-olive-700 underline">Edit<span class="sr-only"> {{ $user->name }}</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-stone-500">No users match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $users->links() }}</div>
    </x-card>
@endsection
