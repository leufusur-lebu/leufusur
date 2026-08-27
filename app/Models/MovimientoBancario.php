<?php

namespace App\Models;

use App\Enums\TipoMovimiento;
use Database\Factories\MovimientoBancarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'fecha',
    'glosa',
    'tipo',
    'monto',
    'conciliado',
    'conciliable_type',
    'conciliable_id',
])]
class MovimientoBancario extends Model
{
    /** @use HasFactory<MovimientoBancarioFactory> */
    use HasFactory;

    protected $table = 'movimientos_bancarios';

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'tipo' => TipoMovimiento::class,
            'monto' => 'decimal:2',
            'conciliado' => 'boolean',
        ];
    }

    /**
     * Registro del sistema al que corresponde el movimiento (FacturaVenta, Gasto o Sueldo).
     */
    public function conciliable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Efecto del movimiento sobre el saldo (positivo abono, negativo cargo).
     */
    public function montoConSigno(): float
    {
        return $this->tipo->signo() * (float) $this->monto;
    }
}
