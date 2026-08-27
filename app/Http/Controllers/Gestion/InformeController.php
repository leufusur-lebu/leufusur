<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\FacturaVenta;
use App\Models\Gasto;
use App\Models\Sueldo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeController extends Controller
{
    /**
     * Reportes disponibles y sus etiquetas.
     */
    public const REPORTES = [
        'facturas-emitidas' => 'Facturas emitidas',
        'facturas-recibidas' => 'Facturas recibidas',
        'iva' => 'Resumen de IVA (F29)',
        'ingresos-gastos' => 'Ingresos vs gastos',
    ];

    /**
     * Descarga un informe en el formato pedido (csv | pdf), acotado por fechas.
     */
    public function __invoke(Request $request, string $reporte, string $formato): Response|StreamedResponse
    {
        abort_unless(array_key_exists($reporte, self::REPORTES), 404);
        abort_unless(in_array($formato, ['csv', 'pdf'], true), 404);

        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $datos = self::datos($reporte, $desde, $hasta);
        $titulo = self::REPORTES[$reporte];
        $subtitulo = self::rangoLegible($desde, $hasta);

        return $formato === 'csv'
            ? $this->csv($reporte, $titulo, $datos)
            : $this->pdf($titulo, $subtitulo, $datos);
    }

    /**
     * Devuelve el informe normalizado: columnas, filas (valores planos), totales y,
     * para las tablas con enlaces, los modelos. Usado por la pantalla y las descargas.
     *
     * @return array{columnas: array<int,string>, filas: array<int,array<int,string>>, totales: array<string,mixed>, items?: Collection}
     */
    public static function datos(string $reporte, ?string $desde, ?string $hasta): array
    {
        return match ($reporte) {
            'facturas-emitidas' => self::facturasEmitidas($desde, $hasta),
            'facturas-recibidas' => self::facturasRecibidas($desde, $hasta),
            'iva' => self::resumenIva($desde, $hasta),
            'ingresos-gastos' => self::ingresosGastos($desde, $hasta),
            default => ['columnas' => [], 'filas' => [], 'totales' => []],
        };
    }

    private static function facturasEmitidas(?string $desde, ?string $hasta): array
    {
        $items = FacturaVenta::with(['cotizacion.cliente', 'proyecto.cliente'])
            ->when($desde, fn ($q) => $q->whereDate('fecha_emision', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_emision', '<=', $hasta))
            ->orderByDesc('fecha_emision')
            ->get();

        $filas = $items->map(fn (FacturaVenta $f) => [
            $f->fecha_emision->format('d-m-Y'),
            $f->numero_factura,
            self::clienteDe($f),
            self::origenDe($f),
            self::money($f->monto_neto),
            self::money($f->iva),
            self::money($f->total_calculado),
            $f->pagada ? 'Pagada' : 'Por cobrar',
        ])->all();

        return [
            'columnas' => ['Fecha', 'N° factura', 'Cliente', 'Origen', 'Neto', 'IVA', 'Total', 'Estado'],
            'filas' => $filas,
            'items' => $items,
            'totales' => [
                'Neto' => self::money($items->sum('monto_neto')),
                'IVA' => self::money($items->sum('iva')),
                'Total' => self::money($items->sum('total_calculado')),
                'Cobrado' => self::money($items->where('pagada', true)->sum('total_calculado')),
                'Por cobrar' => self::money($items->where('pagada', false)->sum('total_calculado')),
            ],
        ];
    }

    private static function facturasRecibidas(?string $desde, ?string $hasta): array
    {
        $items = Gasto::with(['cotizacion', 'proyecto'])
            ->when($desde, fn ($q) => $q->whereDate('fecha_gasto', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_gasto', '<=', $hasta))
            ->orderByDesc('fecha_gasto')
            ->get();

        $filas = $items->map(fn (Gasto $g) => [
            $g->fecha_gasto->format('d-m-Y'),
            $g->numero_documento,
            $g->proveedor,
            self::origenGasto($g),
            self::money($g->monto_neto),
            self::money($g->iva),
            self::money($g->total_calculado),
        ])->all();

        return [
            'columnas' => ['Fecha', 'Documento', 'Proveedor', 'Origen', 'Neto', 'IVA', 'Total'],
            'filas' => $filas,
            'items' => $items,
            'totales' => [
                'Neto' => self::money($items->sum('monto_neto')),
                'IVA' => self::money($items->sum('iva')),
                'Total' => self::money($items->sum('total_calculado')),
            ],
        ];
    }

    private static function resumenIva(?string $desde, ?string $hasta): array
    {
        $debitos = FacturaVenta::when($desde, fn ($q) => $q->whereDate('fecha_emision', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_emision', '<=', $hasta))
            ->get()->groupBy(fn ($f) => $f->fecha_emision->format('Y-m'));
        $creditos = Gasto::when($desde, fn ($q) => $q->whereDate('fecha_gasto', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_gasto', '<=', $hasta))
            ->get()->groupBy(fn ($g) => $g->fecha_gasto->format('Y-m'));

        $periodos = $debitos->keys()->merge($creditos->keys())->unique()->sort()->values();

        $remanente = 0.0;
        $filas = [];
        $totDebito = 0.0;
        $totCredito = 0.0;
        $totPagar = 0.0;

        foreach ($periodos as $periodo) {
            $debito = (float) ($debitos[$periodo] ?? collect())->sum('iva');
            $credito = (float) ($creditos[$periodo] ?? collect())->sum('iva');
            $posicion = $debito - $credito - $remanente;
            $aPagar = max($posicion, 0.0);
            $remanente = $posicion < 0 ? -$posicion : 0.0;

            $filas[] = [
                ucfirst(Carbon::createFromFormat('Y-m', $periodo)->translatedFormat('F Y')),
                self::money($debito),
                self::money($credito),
                self::money($remanente),
                self::money($aPagar),
            ];
            $totDebito += $debito;
            $totCredito += $credito;
            $totPagar += $aPagar;
        }

        return [
            'columnas' => ['Período', 'IVA débito', 'IVA crédito', 'Remanente', 'A pagar SII'],
            'filas' => array_reverse($filas),
            'totales' => [
                'IVA débito' => self::money($totDebito),
                'IVA crédito' => self::money($totCredito),
                'A pagar SII' => self::money($totPagar),
            ],
        ];
    }

    private static function ingresosGastos(?string $desde, ?string $hasta): array
    {
        $facturas = FacturaVenta::when($desde, fn ($q) => $q->whereDate('fecha_emision', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_emision', '<=', $hasta))->get();
        $gastos = Gasto::when($desde, fn ($q) => $q->whereDate('fecha_gasto', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_gasto', '<=', $hasta))->get();
        $sueldos = Sueldo::when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))->get();

        $meses = collect()
            ->merge($facturas->map(fn ($f) => $f->fecha_emision->format('Y-m')))
            ->merge($gastos->map(fn ($g) => $g->fecha_gasto->format('Y-m')))
            ->merge($sueldos->map(fn ($s) => $s->fecha->format('Y-m')))
            ->unique()->sort()->values();

        $filas = [];
        foreach ($meses as $mes) {
            $ing = (float) $facturas->filter(fn ($f) => $f->fecha_emision->format('Y-m') === $mes)->sum('monto_neto');
            $gas = (float) $gastos->filter(fn ($g) => $g->fecha_gasto->format('Y-m') === $mes)->sum('monto_neto');
            $sue = (float) $sueldos->filter(fn ($s) => $s->fecha->format('Y-m') === $mes)->sum('monto');
            $filas[] = [
                ucfirst(Carbon::createFromFormat('Y-m', $mes)->translatedFormat('F Y')),
                self::money($ing),
                self::money($gas),
                self::money($sue),
                self::money($ing - $gas - $sue),
            ];
        }

        $totIng = (float) $facturas->sum('monto_neto');
        $totGas = (float) $gastos->sum('monto_neto');
        $totSue = (float) $sueldos->sum('monto');

        return [
            'columnas' => ['Período', 'Ingresos', 'Gastos', 'Sueldos', 'Resultado'],
            'filas' => array_reverse($filas),
            'totales' => [
                'Ingresos' => self::money($totIng),
                'Gastos' => self::money($totGas),
                'Sueldos' => self::money($totSue),
                'Resultado' => self::money($totIng - $totGas - $totSue),
            ],
        ];
    }

    private function csv(string $reporte, string $titulo, array $datos): StreamedResponse
    {
        $nombre = $reporte.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($datos) {
            $salida = fopen('php://output', 'w');
            // BOM para que Excel reconozca UTF-8 (tildes).
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, $datos['columnas']);
            foreach ($datos['filas'] as $fila) {
                fputcsv($salida, $fila);
            }
            if (! empty($datos['totales'])) {
                fputcsv($salida, []);
                foreach ($datos['totales'] as $etiqueta => $valor) {
                    fputcsv($salida, ['Total '.$etiqueta, $valor]);
                }
            }
            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function pdf(string $titulo, string $subtitulo, array $datos): Response
    {
        return Pdf::loadView('pdf.informe', [
            'titulo' => $titulo,
            'subtitulo' => $subtitulo,
            'columnas' => $datos['columnas'],
            'filas' => $datos['filas'],
            'totales' => $datos['totales'] ?? [],
        ])->download('informe-'.now()->format('Y-m-d').'.pdf');
    }

    public static function rangoLegible(?string $desde, ?string $hasta): string
    {
        if ($desde && $hasta) {
            return 'Del '.Carbon::parse($desde)->format('d-m-Y').' al '.Carbon::parse($hasta)->format('d-m-Y');
        }
        if ($desde) {
            return 'Desde '.Carbon::parse($desde)->format('d-m-Y');
        }
        if ($hasta) {
            return 'Hasta '.Carbon::parse($hasta)->format('d-m-Y');
        }

        return 'Todos los períodos';
    }

    private static function clienteDe(FacturaVenta $f): string
    {
        return $f->cotizacion?->cliente?->nombre ?? $f->proyecto?->cliente?->nombre ?? '—';
    }

    private static function origenDe(FacturaVenta $f): string
    {
        return $f->cotizacion?->numero_cotizacion ?? $f->proyecto?->nombre ?? 'General';
    }

    private static function origenGasto(Gasto $g): string
    {
        return $g->cotizacion?->numero_cotizacion ?? $g->proyecto?->nombre ?? 'General';
    }

    private static function money(float|string|null $valor): string
    {
        return '$'.number_format((float) $valor, 0, ',', '.');
    }
}
