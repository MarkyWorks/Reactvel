<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'Login',
                'Logout',
                'Create User',
                'Update User',
                'Delete User',
            ]),
            'description' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
        ];
    }
}
