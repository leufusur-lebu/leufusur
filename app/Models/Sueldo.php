<?php

namespace App\Models;

use Database\Factories\SueldoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'fecha',
    'monto',
    'glosa',
])]
class Sueldo extends Model
{
    /** @use HasFactory<SueldoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }
}
