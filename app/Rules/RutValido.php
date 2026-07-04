<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class RutValido implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(\d{1,2})\.(\d{3})\.(\d{3})-([\dkK])$/', $value, $matches)) {
            $fail('El :attribute debe tener el formato XX.XXX.XXX-X.');

            return;
        }

        $numero = $matches[1].$matches[2].$matches[3];
        $digitoIngresado = strtoupper($matches[4]);

        if (self::digitoVerificador($numero) !== $digitoIngresado) {
            $fail('El :attribute no es un RUT válido.');
        }
    }

    /**
     * Calcula el dígito verificador de un RUT chileno (algoritmo módulo 11).
     * Público y estático para poder generar RUTs válidos también en tests/factories.
     */
    public static function digitoVerificador(string $numero): string
    {
        $suma = 0;
        $multiplicador = 2;

        foreach (str_split(strrev($numero)) as $digito) {
            $suma += ((int) $digito) * $multiplicador;
            $multiplicador = $multiplicador === 7 ? 2 : $multiplicador + 1;
        }

        $resto = 11 - ($suma % 11);

        return match ($resto) {
            11 => '0',
            10 => 'K',
            default => (string) $resto,
        };
    }
}
