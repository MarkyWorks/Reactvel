<?php

namespace App\Http\Controllers;

use App\Enums\User\UserRoleEnum;
use App\Http\Requests\Users\StoreRequest;
use App\Http\Requests\Users\UpdateRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private function canManageUsers(?User $user): bool
    {
        return in_array($user?->role, [UserRoleEnum::SuperAdmin, UserRoleEnum::Admin], true);
    }

    private function denyIfCannotManage(Request $request): ?RedirectResponse
    {
        if ($this->canManageUsers($request->user())) {
            return null;
        }

        return redirect()
            ->route('users.index')
            ->with('notify', [
                'type' => 'error',
                'message' => 'You are not authorized to manage users.',
            ]);
    }

    private function denyIfAdminEditingSuperAdmin(Request $request, User $user): ?RedirectResponse
    {
        if (
            $request->user()?->role === UserRoleEnum::Admin
            && $user->role === UserRoleEnum::SuperAdmin
        ) {
            return redirect()
                ->route('users.index')
                ->with('notify', [
                    'type' => 'error',
                    'message' => 'Admins cannot edit Super Admin accounts.',
                ]);
        }

        return null;
    }

    private function logAudit(Request $request, string $action, ?User $target = null): void
    {
        $description = $target
            ? "User {$target->name} ({$target->email})"
            : null;

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
        ]);
    }

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
        $user = User::create($request->validated());

        $this->logAudit($request, 'Create User', $user);

        return redirect()
            ->route('users.index')
            ->with('notify', [
                'type' => 'success',
                'message' => 'User created successfully.',
            ]);
    }

    public function update(UpdateRequest $request, User $user): RedirectResponse
    {
        if ($response = $this->denyIfAdminEditingSuperAdmin($request, $user)) {
            return $response;
        }

        $user->update($request->validated());

        $this->logAudit($request, 'Update User', $user);

        return redirect()
            ->route('users.index')
            ->with('notify', [
                'type' => 'success',
                'message' => 'User updated successfully.',
            ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($response = $this->denyIfCannotManage($request)) {
            return $response;
        }

        if (
            $request->user()?->role === UserRoleEnum::Admin
            && $user->role === UserRoleEnum::SuperAdmin
        ) {
            return back()->with('notify', [
                'type' => 'error',
                'message' => 'Admins cannot delete Super Admin accounts.',
            ]);
        }

        if ($request->user()?->is($user)) {
            return back()->with('notify', [
                'type' => 'error',
                'message' => 'You cannot delete your own active account.',
            ]);
        }

        $user->delete();

        $this->logAudit($request, 'Delete User', $user);

        return back()->with('notify', [
            'type' => 'success',
            'message' => 'User deleted successfully.',
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if ($response = $this->denyIfCannotManage($request)) {
            return $response;
        }

        return Inertia::render('users/create', [
            'roleOptions' => array_map(
                fn (UserRoleEnum $roleOption) => $roleOption->value,
                UserRoleEnum::cases()
            ),
        ]);
    }

    public function edit(Request $request, User $user): Response|RedirectResponse
    {
        if ($response = $this->denyIfCannotManage($request)) {
            return $response;
        }

        if ($response = $this->denyIfAdminEditingSuperAdmin($request, $user)) {
            return $response;
        }

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
