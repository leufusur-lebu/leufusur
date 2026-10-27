<?php

namespace App\Models;

use Database\Factories\CotizacionBorradorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'cotizacion_id',
    'payload',
])]
class CotizacionBorrador extends Model
{
    /** @use HasFactory<CotizacionBorradorFactory> */
    use HasFactory;

    /**
     * Plural en español; Laravel no lo infiere ("cotizacion_borradors").
     */
    protected $table = 'cotizacion_borradores';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
