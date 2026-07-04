<?php

namespace Database\Factories;

use App\Enums\TipoCliente;
use App\Models\Cliente;
use App\Rules\RutValido;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => fake()->randomElement(TipoCliente::cases()),
            'nombre' => fake()->name(),
            'nombre_empresa' => null,
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->phoneNumber(),
            'rut_run' => $this->rutValido(),
            'direccion' => fake()->address(),
        ];
    }

    /**
     * Genera un RUT chileno único con dígito verificador correcto,
     * usando el mismo algoritmo que App\Rules\RutValido.
     */
    private function rutValido(): string
    {
        $numero = (string) fake()->unique()->numberBetween(1_000_000, 25_000_000);
        $numeroFormateado = number_format((int) $numero, 0, '', '.');
        $digitoVerificador = RutValido::digitoVerificador($numero);

        return "{$numeroFormateado}-{$digitoVerificador}";
    }
}
