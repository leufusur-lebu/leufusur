<?php

namespace App\Enums;

enum TipoMovimiento: string
{
    case Abono = 'abono';
    case Cargo = 'cargo';

    public function label(): string
    {
        return match ($this) {
            self::Abono => 'Abono (ingreso)',
            self::Cargo => 'Cargo (egreso)',
        };
    }

    /**
     * Signo del movimiento sobre el saldo: +1 para abonos, -1 para cargos.
     */
    public function signo(): int
    {
        return $this === self::Abono ? 1 : -1;
    }
}
