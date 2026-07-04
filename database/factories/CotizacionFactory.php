<?php

namespace Database\Factories;

use App\Enums\EstadoCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cotizacion>
 */
class CotizacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            // No usa Cotizacion::generarNumero(): en creaciones por lote (::factory()->count(n)->create())
            // Laravel resuelve todas las definiciones antes de insertar cualquiera, así que el conteo en BD
            // sería el mismo para las n filas. fake()->unique() sí es seguro entre llamadas del mismo lote.
            'numero_cotizacion' => 'CTZ-'.now()->format('Ymd').'-'.fake()->unique()->numerify('###'),
            'descripcion' => fake()->sentence(4),
            'fecha_emision' => today(),
            'fecha_vencimiento' => today()->addDays(30),
            'descuento_porcentaje' => 0,
            'estado' => EstadoCotizacion::Borrador,
            'id_mercado_publico' => null,
            'archivo_adjunto' => null,
        ];
    }
}
