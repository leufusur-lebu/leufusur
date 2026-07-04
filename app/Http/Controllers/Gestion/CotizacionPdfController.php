<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class CotizacionPdfController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Cotizacion $cotizacion): Response
    {
        $cotizacion->load('cliente', 'lineas');

        return Pdf::loadView('pdf.cotizacion', ['cotizacion' => $cotizacion])
            ->download("{$cotizacion->numero_cotizacion}.pdf");
    }
}
