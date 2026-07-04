<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CotizacionAdjuntoController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Cotizacion $cotizacion): StreamedResponse
    {
        abort_unless($cotizacion->archivo_adjunto, 404);

        return Storage::disk('local')->download($cotizacion->archivo_adjunto);
    }
}
