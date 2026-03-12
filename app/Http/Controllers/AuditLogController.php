<?php

namespace App\Http\Controllers;

use App\Enums\User\UserRoleEnum;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    private function canViewAuditLogs(?User $user): bool
    {
        return in_array($user?->role, [UserRoleEnum::SuperAdmin, UserRoleEnum::Admin], true);
    }

    public function index(Request $request): Response|RedirectResponse
    {
        if (! $this->canViewAuditLogs($request->user())) {
            return redirect()
                ->route('dashboard')
                ->with('notify', [
                    'type' => 'error',
                    'message' => 'You are not authorized to view audit logs.',
                ]);
        }

        $search = (string) $request->string('search')->trim();
        $status = (string) $request->string('status')->trim();
        $now = now();
        $onlineCutoff = $now->copy()->subMinute();
        $activeCutoff = $now->copy()->subMinutes(5);
        $inactiveCutoff = $now->copy()->subMinutes(30);
        $statusFilter = function ($query) use ($status, $onlineCutoff, $activeCutoff, $inactiveCutoff) {
            if ($status === 'online') {
                $query
                    ->whereNotNull('last_seen_at')
                    ->where('last_seen_at', '>=', $onlineCutoff)
                    ->where(function ($condition) {
                        $condition
                            ->whereNull('last_logged_out_at')
                            ->orWhereColumn('last_logged_out_at', '<', 'last_seen_at');
                    });
            }

            if ($status === 'active') {
                $query
                    ->whereNotNull('last_seen_at')
                    ->where('last_seen_at', '<', $onlineCutoff)
                    ->where('last_seen_at', '>=', $activeCutoff)
                    ->where(function ($condition) {
                        $condition
                            ->whereNull('last_logged_out_at')
                            ->orWhereColumn('last_logged_out_at', '<', 'last_seen_at');
                    });
            }

            if ($status === 'inactive') {
                $query
                    ->whereNotNull('last_seen_at')
                    ->where('last_seen_at', '<', $activeCutoff)
                    ->where('last_seen_at', '>=', $inactiveCutoff)
                    ->where(function ($condition) {
                        $condition
                            ->whereNull('last_logged_out_at')
                            ->orWhereColumn('last_logged_out_at', '<', 'last_seen_at');
                    });
            }

            if ($status === 'offline') {
                $query->where(function ($offlineQuery) use ($inactiveCutoff) {
                    $offlineQuery
                        ->whereNull('last_seen_at')
                        ->orWhere('last_seen_at', '<', $inactiveCutoff)
                        ->orWhereColumn('last_logged_out_at', '>=', 'last_seen_at');
                });
            }
        };

        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'last_seen_at', 'last_logged_out_at'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($statusFilter) {
                $query->where(function ($builder) use ($statusFilter) {
                    $statusFilter($builder);
                });
            })
            ->orderByDesc('last_seen_at')
            ->paginate(10)
            ->through(function (User $user) {
                $lastActivityAt = $user->status === 'offline'
                    ? ($user->last_logged_out_at ?? $user->last_seen_at)
                    : $user->last_seen_at;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ?? null,
                    'status' => $user->status,
                    'last_active_at' => $lastActivityAt?->toDateTimeString(),
                    'last_active_label' => $lastActivityAt?->diffForHumans(),
                ];
            })
            ->withQueryString();

        $logs = AuditLog::query()
            ->with('user:id,name,email,last_seen_at,last_logged_out_at')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('id', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', function ($query) use ($statusFilter) {
                $query->whereHas('user', function ($userQuery) use ($statusFilter) {
                    $statusFilter($userQuery);
                });
            })
            ->latest()
            ->paginate(10)
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'user' => $log->user?->name ?? 'System',
                'action' => $log->action,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toDateTimeString(),
            ])
            ->withQueryString();

        return Inertia::render('audit-logs/index', [
            'users' => $users,
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => ['online', 'active', 'inactive', 'offline'],
        ]);
    }
}
