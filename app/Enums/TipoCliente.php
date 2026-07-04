<?php

namespace App\Enums;

enum TipoCliente: string
{
    case Persona = 'persona';
    case Empresa = 'empresa';

    public function label(): string
    {
        return match ($this) {
            self::Persona => 'Persona natural',
            self::Empresa => 'Empresa',
        };
    }
}
