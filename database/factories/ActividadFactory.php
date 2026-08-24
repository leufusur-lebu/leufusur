<?php

namespace Database\Factories;

use App\Models\Actividad;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Actividad>
 */
class ActividadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = fake()->numberBetween(8, 15);
        $duracion = fake()->numberBetween(1, 4);

        return [
            'proyecto_id' => Proyecto::factory(),
            'fecha' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'hora_inicio' => sprintf('%02d:00', $inicio),
            'hora_termino' => sprintf('%02d:00', $inicio + $duracion),
            'lugar' => fake()->streetAddress(),
            'descripcion' => fake()->sentence(8),
        ];
    }
}
