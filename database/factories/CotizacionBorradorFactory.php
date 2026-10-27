<?php

namespace Database\Factories;

use App\Models\CotizacionBorrador;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CotizacionBorrador>
 */
class CotizacionBorradorFactory extends Factory
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
            'cotizacion_id' => null,
            'payload' => [
                'descripcion' => fake()->sentence(3),
                'lineas' => [],
            ],
        ];
    }
}
