<?php

namespace App\Models;

use App\Enums\EstadoProyecto;
use Database\Factories\ProyectoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'cliente_id',
    'nombre',
    'descripcion',
    'fecha_inicio',
    'estado',
    'anticipo_monto',
    'anticipo_pagado',
    'anticipo_fecha_pago',
])]
class Proyecto extends Model
{
    /** @use HasFactory<ProyectoFactory> */
    use HasFactory;

    protected $table = 'proyectos';

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'estado' => EstadoProyecto::class,
            'anticipo_monto' => 'decimal:2',
            'anticipo_pagado' => 'boolean',
            'anticipo_fecha_pago' => 'date',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function facturaVenta(): HasOne
    {
        return $this->hasOne(FacturaVenta::class);
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(Actividad::class)->orderByDesc('fecha')->orderByDesc('hora_inicio');
    }
}
