<?php

namespace App\Enums\User;

enum UserRoleEnum: string
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case User = 'User';
}
