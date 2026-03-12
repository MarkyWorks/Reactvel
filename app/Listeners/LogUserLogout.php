<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $userId = $event->user?->id;

        if ($userId) {
            $event->user->forceFill([
                'last_logged_out_at' => now(),
            ])->save();

            $latestLog = AuditLog::query()
                ->where('user_id', $userId)
                ->where('action', 'Logout')
                ->latest()
                ->first();

            if ($latestLog && $latestLog->created_at?->diffInSeconds(now()) <= 10) {
                return;
            }
        }

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'Logout',
            'description' => 'User logged out.',
            'ip_address' => request()->ip(),
        ]);
    }
}
