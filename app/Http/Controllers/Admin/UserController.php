<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('email', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $users->through(function (User $user) {
            /** @var Role|null $role */
            $role = $user->roles->first();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role?->name,
            ];
        });

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $user->syncRoles([$request->validated('role')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rôle mis à jour.']);

        return to_route('admin.users.index');
    }
}
