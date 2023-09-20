<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\projects_users>
 */
class ProjectsUsersFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'user_id' => fake()->randomDigit(),
            'project_id' => fake()->randomDigit(),
            'permission_id' => fake()->randomDigit(),
        ];
    }
}
