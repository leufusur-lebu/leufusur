<?php

namespace App\Models;

use Database\Factories\LineaAmpliacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ampliacion_id', 'descripcion', 'unidad', 'cantidad', 'valor_unitario', 'subtotal_calculado', 'orden'])]
class LineaAmpliacion extends Model
{
    /** @use HasFactory<LineaAmpliacionFactory> */
    use HasFactory;

    /**
     * Tabla real es "lineas_ampliacion" (singular en el segundo término); no se puede inferir.
     */
    protected $table = 'lineas_ampliacion';

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'valor_unitario' => 'decimal:2',
            'subtotal_calculado' => 'decimal:2',
        ];
    }

    public function ampliacion(): BelongsTo
    {
        return $this->belongsTo(Ampliacion::class);
    }
}
