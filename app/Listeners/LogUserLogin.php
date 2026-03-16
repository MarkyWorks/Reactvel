<?php

namespace App\Listeners;

use App\Events\AuditLogCreated;
use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;

class LogUserLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $userId = $event->user?->id;

        if ($userId) {
            $latestLog = AuditLog::query()
                ->where('user_id', $userId)
                ->where('action', 'Login')
                ->latest()
                ->first();

            if ($latestLog && $latestLog->created_at?->diffInSeconds(now()) <= 10) {
                return;
            }
        }

        $auditLog = AuditLog::create([
            'user_id' => $userId,
            'action' => 'Login',
            'description' => 'User logged in.',
            'ip_address' => request()->ip(),
        ]);

        event(new AuditLogCreated($auditLog->id));
    }
}
