<?php

namespace Database\Factories;

use App\Enums\EstadoProyecto;
use App\Models\Cliente;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proyecto>
 */
class ProyectoFactory extends Factory
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
            'nombre' => fake()->sentence(3),
            'descripcion' => fake()->optional()->paragraph(),
            'fecha_inicio' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'estado' => EstadoProyecto::Activo,
        ];
    }

    /**
     * Proyecto con el anticipo (50% inicial) ya pagado.
     */
    public function conAnticipo(float $monto = 250000, ?string $fecha = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'anticipo_monto' => $monto,
            'anticipo_pagado' => true,
            'anticipo_fecha_pago' => $fecha ?? now()->toDateString(),
        ]);
    }
}
