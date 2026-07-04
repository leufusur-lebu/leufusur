<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Gasto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GastoComprobanteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Gasto $gasto): StreamedResponse
    {
        abort_unless($gasto->archivo_comprobante, 404);

        return Storage::disk('local')->download($gasto->archivo_comprobante);
    }
}
