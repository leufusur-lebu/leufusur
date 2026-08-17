<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\FacturaVenta;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacturaVentaArchivoController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(FacturaVenta $facturaVenta): StreamedResponse
    {
        abort_unless($facturaVenta->archivo_pdf, 404);

        return Storage::disk('local')->download($facturaVenta->archivo_pdf);
    }
}
