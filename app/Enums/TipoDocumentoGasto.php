<?php

namespace App\Enums;

enum TipoDocumentoGasto: string
{
    case Factura = 'factura';
    case Boleta = 'boleta';

    public function label(): string
    {
        return match ($this) {
            self::Factura => 'Factura',
            self::Boleta => 'Boleta',
        };
    }
}
