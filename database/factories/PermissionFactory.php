<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->randomDigit(),
            'project_id' => fake()->randomDigit(),
            'name' => fake()->word(),
            'read' => fake()->numberBetween(0, 1),
            'write' => fake()->numberBetween(0, 1),
            'givePermissions' => fake()->numberBetween(0, 1),
        ];
    }
}
