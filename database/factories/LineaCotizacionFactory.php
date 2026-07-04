<?php

namespace Database\Factories;

use App\Models\Cotizacion;
use App\Models\LineaCotizacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LineaCotizacion>
 */
class LineaCotizacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = fake()->numberBetween(1, 10);
        $valorUnitario = fake()->numberBetween(5000, 200000);

        return [
            'cotizacion_id' => Cotizacion::factory(),
            'descripcion' => fake()->sentence(3),
            'cantidad' => $cantidad,
            'valor_unitario' => $valorUnitario,
            'subtotal_calculado' => $cantidad * $valorUnitario,
            'orden' => 0,
        ];
    }
}
