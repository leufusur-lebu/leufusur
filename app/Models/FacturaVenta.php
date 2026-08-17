<?php

namespace App\Models;

use Database\Factories\FacturaVentaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cotizacion_id',
    'numero_factura',
    'fecha_emision',
    'descripcion',
    'monto_neto',
    'iva',
    'total_calculado',
    'archivo_pdf',
])]
class FacturaVenta extends Model
{
    /** @use HasFactory<FacturaVentaFactory> */
    use HasFactory;

    protected $table = 'facturas_venta';

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'monto_neto' => 'decimal:2',
            'iva' => 'decimal:2',
            'total_calculado' => 'decimal:2',
        ];
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
