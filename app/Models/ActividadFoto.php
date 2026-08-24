<?php

namespace App\Models;

use Database\Factories\ActividadFotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'actividad_id',
    'ruta',
])]
class ActividadFoto extends Model
{
    /** @use HasFactory<ActividadFotoFactory> */
    use HasFactory;

    protected $table = 'actividad_fotos';

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }
}
