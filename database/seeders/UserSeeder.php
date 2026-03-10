<?php

namespace Database\Seeders;

use App\Enums\User\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'role' => UserRoleEnum::SuperAdmin->value,
                'password' => 'superadmin123',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => UserRoleEnum::Admin->value,
                'password' => 'admin123',
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'role' => UserRoleEnum::User->value,
                'password' => 'user123',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['remember_token' => Str::random(10)])
            );
        }
    }
}
