<?php

namespace App\Http\Middleware;

use App\Events\UserActivityUpdated;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeenAt
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();

        if ($user) {
            $now = now();
            $threshold = $now->copy()->subMinute();

            if (! $user->last_seen_at || $user->last_seen_at->lt($threshold)) {
                $user->forceFill([
                    'last_seen_at' => $now,
                ])->save();

                event(new UserActivityUpdated($user->id));
            }
        }

        return $response;
    }
}
