<?php

namespace App\Http\Controllers;

use App\Enums\User\UserRoleEnum;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $now = now();

        $totalUsers = User::query()->count();
        $activeToday = User::query()
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', $now->copy()->startOfDay())
            ->count();
        $newUsers = User::query()
            ->where('created_at', '>=', $now->copy()->subDays(7))
            ->count();
        $adminCount = User::query()
            ->whereIn('role', [UserRoleEnum::SuperAdmin, UserRoleEnum::Admin])
            ->count();

        $recentUsers = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value ?? null,
                'created_at' => $user->created_at?->toDateTimeString(),
            ]);

        $recentActivity = AuditLog::query()
            ->with('user:id,name,email')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'user' => $log->user?->name ?? 'System',
                'action' => $log->action,
                'description' => $log->description,
                'created_at' => $log->created_at?->toDateTimeString(),
            ]);

        $roleDistribution = collect(UserRoleEnum::cases())
            ->map(fn (UserRoleEnum $role) => [
                'role' => $role->value,
                'count' => User::query()->where('role', $role)->count(),
            ])
            ->values();

        $loginLast24h = AuditLog::query()
            ->where('action', 'Login')
            ->where('created_at', '>=', $now->copy()->subDay())
            ->count();
        $logoutLast24h = AuditLog::query()
            ->where('action', 'Logout')
            ->where('created_at', '>=', $now->copy()->subDay())
            ->count();

        return Inertia::render('dashboard', [
            'kpis' => [
                'total_users' => $totalUsers,
                'active_today' => $activeToday,
                'new_users' => $newUsers,
                'admins' => $adminCount,
            ],
            'recentActivity' => $recentActivity,
            'recentUsers' => $recentUsers,
            'roleDistribution' => $roleDistribution,
            'securitySnapshot' => [
                'logins_last_24h' => $loginLast24h,
                'logouts_last_24h' => $logoutLast24h,
            ],
        ]);
    }
}
