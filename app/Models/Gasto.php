<?php

namespace App\Models;

use App\Enums\TipoDocumentoGasto;
use Database\Factories\GastoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cotizacion_id',
    'proyecto_id',
    'fecha_gasto',
    'tipo',
    'numero_documento',
    'proveedor',
    'descripcion',
    'monto_neto',
    'iva',
    'total_calculado',
    'archivo_comprobante',
])]
class Gasto extends Model
{
    /** @use HasFactory<GastoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha_gasto' => 'date',
            'tipo' => TipoDocumentoGasto::class,
            'monto_neto' => 'decimal:2',
            'iva' => 'decimal:2',
            'total_calculado' => 'decimal:2',
        ];
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
