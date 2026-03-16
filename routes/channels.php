<?php

use App\Enums\User\UserRoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

Broadcast::channel('audit-logs', function (?User $user): bool {
    return in_array($user?->role, [UserRoleEnum::SuperAdmin, UserRoleEnum::Admin], true);
});
