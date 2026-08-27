<?php

namespace Database\Factories;

use App\Models\Sueldo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sueldo>
 */
class SueldoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'monto' => fake()->numberBetween(400000, 1500000),
            'glosa' => fake()->optional()->sentence(3),
        ];
    }
}
