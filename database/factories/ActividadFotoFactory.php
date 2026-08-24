<?php

namespace Database\Factories;

use App\Models\Actividad;
use App\Models\ActividadFoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActividadFoto>
 */
class ActividadFotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actividad_id' => Actividad::factory(),
            'ruta' => 'actividades/'.fake()->uuid().'.jpg',
        ];
    }
}
