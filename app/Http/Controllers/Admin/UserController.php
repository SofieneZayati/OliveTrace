<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Mill;
use App\Models\User;
use App\Services\UserAdministration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(Role::class)],
            'active' => ['nullable', Rule::in(['0', '1'])],
        ]);
        $users = User::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when(isset($filters['active']), fn ($query) => $query->where('is_active', $filters['active']))
            ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::cases()]);
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user,
            'mill' => Mill::withTrashed()->where('user_id', $user->id)->first(),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::cases(),
            'mill' => Mill::withTrashed()->where('user_id', $user->id)->first(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UserAdministration $administration): RedirectResponse
    {
        $administration->update($request->user(), $user, $request->validated());

        return redirect()->route('admin.users.show', $user)->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user, UserAdministration $administration): RedirectResponse
    {
        $administration->delete($request->user(), $user);

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
