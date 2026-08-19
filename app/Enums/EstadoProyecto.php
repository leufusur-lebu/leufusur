<?php

namespace App\Enums;

enum EstadoProyecto: string
{
    case Activo = 'activo';
    case Facturado = 'facturado';
    case Cerrado = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Facturado => 'Facturado',
            self::Cerrado => 'Cerrado',
        };
    }
}
