<?php

namespace App\Models;

use Database\Factories\LineaCotizacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cotizacion_id', 'descripcion', 'detalle_extendido', 'unidad', 'cantidad', 'valor_unitario', 'subtotal_calculado', 'orden'])]
class LineaCotizacion extends Model
{
    /** @use HasFactory<LineaCotizacionFactory> */
    use HasFactory;

    /**
     * Tabla real es "lineas_cotizacion" (singular en el segundo término); no se puede inferir.
     */
    protected $table = 'lineas_cotizacion';

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'valor_unitario' => 'decimal:2',
            'subtotal_calculado' => 'decimal:2',
        ];
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
