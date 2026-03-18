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
                'email' => 'markcleocalbang22@gmail.com',
                'role' => UserRoleEnum::SuperAdmin->value,
                'campus_id' => '1002',
                'password' => 'superadmin123',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => UserRoleEnum::Admin->value,
                'campus_id' => '1003',
                'password' => 'admin123',
            ],
            [
                'name' => 'Faculty Member',
                'email' => 'joseelroycalbang@gmail.com',
                'role' => UserRoleEnum::Faculty->value,
                'campus_id' => '1001',
                'password' => 'faculty123',
            ],
            [
                'name' => 'Mark Cleo Calbang',
                'email' => 'markcleocalbang05@gmail.com',
                'role' => UserRoleEnum::Student->value,
                'campus_id' => '8220905',
                'password' => 'M@rky123',
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
