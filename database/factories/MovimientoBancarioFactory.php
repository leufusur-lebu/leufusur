<?php

namespace Database\Factories;

use App\Enums\TipoMovimiento;
use App\Models\MovimientoBancario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovimientoBancario>
 */
class MovimientoBancarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'glosa' => fake()->sentence(3),
            'tipo' => fake()->randomElement(TipoMovimiento::cases()),
            'monto' => fake()->numberBetween(10000, 2000000),
            'conciliado' => false,
            'conciliable_type' => null,
            'conciliable_id' => null,
        ];
    }

    public function abono(): static
    {
        return $this->state(fn () => ['tipo' => TipoMovimiento::Abono]);
    }

    public function cargo(): static
    {
        return $this->state(fn () => ['tipo' => TipoMovimiento::Cargo]);
    }
}
