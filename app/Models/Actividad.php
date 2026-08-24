<?php

namespace App\Models;

use Database\Factories\ActividadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'proyecto_id',
    'fecha',
    'hora_inicio',
    'hora_termino',
    'lugar',
    'descripcion',
])]
class Actividad extends Model
{
    /** @use HasFactory<ActividadFactory> */
    use HasFactory;

    protected $table = 'actividades';

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(ActividadFoto::class);
    }

    /**
     * Duración de la actividad en minutos (asume mismo día, término ≥ inicio).
     */
    public function duracionEnMinutos(): int
    {
        $inicio = Carbon::createFromFormat('H:i', substr($this->hora_inicio, 0, 5));
        $termino = Carbon::createFromFormat('H:i', substr($this->hora_termino, 0, 5));

        return max(0, $inicio->diffInMinutes($termino));
    }

    /**
     * Duración formateada como "Xh YYm".
     */
    public function duracionLegible(): string
    {
        $minutos = $this->duracionEnMinutos();

        return intdiv($minutos, 60).'h '.str_pad((string) ($minutos % 60), 2, '0', STR_PAD_LEFT).'m';
    }
}
