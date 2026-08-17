<?php

namespace Database\Factories;

use App\Models\Cotizacion;
use App\Models\FacturaVenta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacturaVenta>
 */
class FacturaVentaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $neto = fake()->numberBetween(100000, 5000000);
        $iva = (int) round($neto * Cotizacion::IVA);

        return [
            'cotizacion_id' => Cotizacion::factory(),
            'numero_factura' => (string) fake()->numberBetween(1, 9999),
            'fecha_emision' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'descripcion' => fake()->sentence(4),
            'monto_neto' => $neto,
            'iva' => $iva,
            'total_calculado' => $neto + $iva,
            'archivo_pdf' => null,
        ];
    }
}
