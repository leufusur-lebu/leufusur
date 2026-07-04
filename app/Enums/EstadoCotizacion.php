<?php

namespace App\Enums;

enum EstadoCotizacion: string
{
    case Borrador = 'borrador';
    case Enviada = 'enviada';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviada => 'Enviada',
            self::Aprobada => 'Aprobada',
            self::Rechazada => 'Rechazada',
        };
    }

    /**
     * Una cotización enviada, aprobada o rechazada ya no se puede editar.
     */
    public function esEditable(): bool
    {
        return $this === self::Borrador;
    }
}
