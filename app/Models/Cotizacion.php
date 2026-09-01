<?php

namespace App\Models;

use App\Enums\EstadoCotizacion;
use Database\Factories\CotizacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RuntimeException;

#[Fillable([
    'cliente_id',
    'numero_cotizacion',
    'descripcion',
    'fecha_emision',
    'fecha_vencimiento',
    'subtotal_calculado',
    'descuento_porcentaje',
    'base_gravada_calculada',
    'iva_calculado',
    'total_calculado',
    'estado',
    'id_mercado_publico',
    'archivo_adjunto',
    'anticipo_monto',
    'anticipo_pagado',
    'anticipo_fecha_pago',
])]
class Cotizacion extends Model
{
    /** @use HasFactory<CotizacionFactory> */
    use HasFactory;

    /**
     * Laravel pluraliza "Cotizacion" como "cotizacions" (reglas en inglés); se fija explícito.
     */
    protected $table = 'cotizaciones';

    /**
     * Tasa de IVA en Chile. No configurable: es una constante legal, no una preferencia.
     */
    public const IVA = 0.19;

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'subtotal_calculado' => 'decimal:2',
            'descuento_porcentaje' => 'decimal:2',
            'base_gravada_calculada' => 'decimal:2',
            'iva_calculado' => 'decimal:2',
            'total_calculado' => 'decimal:2',
            'estado' => EstadoCotizacion::class,
            'enviada_en' => 'datetime',
            'aprobada_en' => 'datetime',
            'rechazada_en' => 'datetime',
            'anticipo_monto' => 'decimal:2',
            'anticipo_pagado' => 'boolean',
            'anticipo_fecha_pago' => 'date',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(LineaCotizacion::class)->orderBy('orden');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function facturaVenta(): HasOne
    {
        return $this->hasOne(FacturaVenta::class);
    }

    /**
     * Genera el siguiente número correlativo del día: CTZ-YYYYMMDD-XXX.
     */
    public static function generarNumero(): string
    {
        $consecutivo = static::whereDate('created_at', today())->count() + 1;

        return sprintf('CTZ-%s-%03d', now()->format('Ymd'), $consecutivo);
    }

    /**
     * Recalcula subtotal, base gravada, IVA y total a partir de las líneas actuales.
     */
    public function recalcularTotales(): void
    {
        $subtotal = $this->lineas()->sum('subtotal_calculado');
        $baseGravada = $subtotal - ($subtotal * $this->descuento_porcentaje / 100);
        $iva = $baseGravada * self::IVA;

        $this->forceFill([
            'subtotal_calculado' => $subtotal,
            'base_gravada_calculada' => $baseGravada,
            'iva_calculado' => $iva,
            'total_calculado' => $baseGravada + $iva,
        ])->save();
    }

    public function marcarEnviada(): void
    {
        $this->transicionar(EstadoCotizacion::Borrador, EstadoCotizacion::Enviada, 'enviada_en');
    }

    public function marcarAprobada(): void
    {
        $this->transicionar(EstadoCotizacion::Enviada, EstadoCotizacion::Aprobada, 'aprobada_en');
    }

    public function marcarRechazada(): void
    {
        $this->transicionar(EstadoCotizacion::Enviada, EstadoCotizacion::Rechazada, 'rechazada_en');
    }

    private function transicionar(EstadoCotizacion $desde, EstadoCotizacion $hacia, string $columnaFecha): void
    {
        if ($this->estado !== $desde) {
            throw new RuntimeException(
                "No se puede pasar de \"{$this->estado->label()}\" a \"{$hacia->label()}\"."
            );
        }

        $this->forceFill([
            'estado' => $hacia,
            $columnaFecha => now(),
        ])->save();
    }

    /**
     * Duplica la cotización (y sus líneas) como un nuevo borrador.
     * Copia todo excepto estado, número y adjunto. Las fechas se re-anclan a hoy,
     * preservando los mismos días de validez (para no heredar un vencimiento ya pasado).
     */
    public function duplicar(): self
    {
        $diasValidez = $this->fecha_emision->diffInDays($this->fecha_vencimiento);

        $nueva = self::create([
            'cliente_id' => $this->cliente_id,
            'numero_cotizacion' => self::generarNumero(),
            'descripcion' => $this->descripcion,
            'fecha_emision' => today(),
            'fecha_vencimiento' => today()->addDays($diasValidez),
            'descuento_porcentaje' => $this->descuento_porcentaje,
            'estado' => EstadoCotizacion::Borrador,
            'id_mercado_publico' => $this->id_mercado_publico,
        ]);

        foreach ($this->lineas as $linea) {
            $nueva->lineas()->create([
                'descripcion' => $linea->descripcion,
                'detalle_extendido' => $linea->detalle_extendido,
                'unidad' => $linea->unidad,
                'cantidad' => $linea->cantidad,
                'valor_unitario' => $linea->valor_unitario,
                'subtotal_calculado' => $linea->subtotal_calculado,
                'orden' => $linea->orden,
            ]);
        }

        $nueva->recalcularTotales();

        return $nueva;
    }
}
