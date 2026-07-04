<?php

namespace Database\Factories;

use App\Enums\TipoDocumentoGasto;
use App\Models\Cotizacion;
use App\Models\Gasto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gasto>
 */
class GastoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $montoNeto = fake()->numberBetween(5000, 200000);
        $iva = round($montoNeto * 0.19);

        return [
            'cotizacion_id' => Cotizacion::factory(),
            'fecha_gasto' => today(),
            'tipo' => fake()->randomElement(TipoDocumentoGasto::cases()),
            'numero_documento' => (string) fake()->unique()->numberBetween(1000, 999999),
            'proveedor' => fake()->company(),
            'descripcion' => fake()->sentence(3),
            'monto_neto' => $montoNeto,
            'iva' => $iva,
            'total_calculado' => $montoNeto + $iva,
            'archivo_comprobante' => null,
        ];
    }
}
