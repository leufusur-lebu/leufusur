<?php

namespace App\Models;

use Database\Factories\AmpliacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'cotizacion_id',
    'fecha',
    'descripcion',
    'subtotal_calculado',
    'iva_calculado',
    'total_calculado',
])]
class Ampliacion extends Model
{
    /** @use HasFactory<AmpliacionFactory> */
    use HasFactory;

    /**
     * Plural correcto en español; Laravel no lo infiere ("ampliacions").
     */
    protected $table = 'ampliaciones';

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'subtotal_calculado' => 'decimal:2',
            'iva_calculado' => 'decimal:2',
            'total_calculado' => 'decimal:2',
        ];
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(LineaAmpliacion::class)->orderBy('orden');
    }

    /**
     * Recalcula subtotal, IVA y total a partir de las líneas actuales.
     * No lleva descuento (a diferencia de la cotización): el neto es el subtotal.
     */
    public function recalcularTotales(): void
    {
        $subtotal = $this->lineas()->sum('subtotal_calculado');
        $iva = $subtotal * Cotizacion::IVA;

        $this->forceFill([
            'subtotal_calculado' => $subtotal,
            'iva_calculado' => $iva,
            'total_calculado' => $subtotal + $iva,
        ])->save();
    }
}
