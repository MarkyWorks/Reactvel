<?php

namespace App\Http\Controllers;

use App\Enums\User\UserRoleEnum;
use App\Http\Requests\Users\StoreRequest;
use App\Http\Requests\Users\UpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->string('search')->trim();
        $role = (string) $request->string('role')->trim();

        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->latest()
            ->paginate(5)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? null,
                'created_at' => $user->created_at?->toDateTimeString(),
            ])
            ->withQueryString();

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
            'roleOptions' => array_map(
                fn (UserRoleEnum $roleOption) => $roleOption->value,
                UserRoleEnum::cases()
            ),
        ]);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('notify', [
                'type' => 'success',
                'message' => 'User created successfully.',
            ]);
    }

    public function update(UpdateRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()
            ->route('users.index')
            ->with('notify', [
                'type' => 'success',
                'message' => 'User updated successfully.',
            ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()->with('notify', [
                'type' => 'error',
                'message' => 'You cannot delete your own active account.',
            ]);
        }

        $user->delete();

        return back()->with('notify', [
            'type' => 'success',
            'message' => 'User deleted successfully.',
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('users/create', [
            'roleOptions' => array_map(
                fn (UserRoleEnum $roleOption) => $roleOption->value,
                UserRoleEnum::cases()
            ),
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? null,
            ],
            'roleOptions' => array_map(
                fn (UserRoleEnum $roleOption) => $roleOption->value,
                UserRoleEnum::cases()
            ),
        ]);
    }
}
