<?php

namespace Database\Factories;

use App\Models\Ampliacion;
use App\Models\Cotizacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ampliacion>
 */
class AmpliacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(50000, 400000);
        $iva = $subtotal * Cotizacion::IVA;

        return [
            'cotizacion_id' => Cotizacion::factory(),
            'fecha' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'descripcion' => fake()->sentence(3),
            'subtotal_calculado' => $subtotal,
            'iva_calculado' => $iva,
            'total_calculado' => $subtotal + $iva,
        ];
    }
}
