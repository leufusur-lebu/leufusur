<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>{{ $cotizacion->numero_cotizacion }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #0e7490;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .empresa {
            font-size: 18px;
            font-weight: bold;
            color: #0e7490;
        }

        .empresa-detalle {
            color: #6b7280;
            font-size: 10px;
        }

        .cotizacion-numero {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
        }

        .cotizacion-fechas {
            text-align: right;
            color: #6b7280;
            font-size: 10px;
        }

        .seccion {
            margin-bottom: 16px;
        }

        .seccion-titulo {
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .cliente-nombre {
            font-size: 13px;
            font-weight: bold;
        }

        table.lineas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table.lineas th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }

        table.lineas td {
            padding: 6px 8px;
            border-bottom: 1px solid #f3f4f6;
        }

        .num {
            text-align: right;
        }

        table.resumen {
            width: 260px;
            margin-left: auto;
            margin-top: 10px;
        }

        table.resumen td {
            padding: 3px 0;
        }

        table.resumen .total td {
            border-top: 1px solid #d1d5db;
            font-weight: bold;
            font-size: 13px;
            padding-top: 6px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 9px;
            text-align: center;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td width="50%">
                <div class="empresa">Leufu Sur SpA</div>
                <div class="empresa-detalle">Servicios informáticos, seguridad y redes</div>
                <div class="empresa-detalle">contacto@leufusur.cl &middot; +56 9 1234 5678</div>
            </td>
            <td width="50%">
                <div class="cotizacion-numero">{{ $cotizacion->numero_cotizacion }}</div>
                <div class="cotizacion-fechas">
                    Emisión: {{ $cotizacion->fecha_emision->format('d-m-Y') }}<br>
                    Vencimiento: {{ $cotizacion->fecha_vencimiento->format('d-m-Y') }}
                    @if ($cotizacion->id_mercado_publico)
                        <br>ID Mercado Público: {{ $cotizacion->id_mercado_publico }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="seccion">
        <div class="seccion-titulo">Cliente</div>
        <div class="cliente-nombre">{{ $cotizacion->cliente->nombre }}</div>
        @if ($cotizacion->cliente->nombre_empresa)
            <div>{{ $cotizacion->cliente->nombre_empresa }}</div>
        @endif
        <div>{{ $cotizacion->cliente->rut_run }}</div>
        <div>{{ $cotizacion->cliente->direccion }}</div>
        <div>{{ $cotizacion->cliente->email }} &middot; {{ $cotizacion->cliente->telefono }}</div>
    </div>

    <div class="seccion">
        <div class="seccion-titulo">{{ $cotizacion->descripcion }}</div>

        <table class="lineas">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="num">Cantidad</th>
                    <th class="num">Valor unitario</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cotizacion->lineas as $linea)
                    <tr>
                        <td>{{ $linea->descripcion }}</td>
                        <td class="num">{{ rtrim(rtrim($linea->cantidad, '0'), '.') }}</td>
                        <td class="num">${{ number_format($linea->valor_unitario, 0, ',', '.') }}</td>
                        <td class="num">${{ number_format($linea->subtotal_calculado, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="resumen">
            <tr>
                <td>Subtotal</td>
                <td class="num">${{ number_format($cotizacion->subtotal_calculado, 0, ',', '.') }}</td>
            </tr>
            @if ((float) $cotizacion->descuento_porcentaje > 0)
                <tr>
                    <td>Base gravada (desc. {{ rtrim(rtrim($cotizacion->descuento_porcentaje, '0'), '.') }}%)</td>
                    <td class="num">${{ number_format($cotizacion->base_gravada_calculada, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr>
                <td>IVA (19%)</td>
                <td class="num">${{ number_format($cotizacion->iva_calculado, 0, ',', '.') }}</td>
            </tr>
            <tr class="total">
                <td>Total</td>
                <td class="num">${{ number_format($cotizacion->total_calculado, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Leufu Sur SpA &middot; Proveedor del Estado a través de Mercado Público &middot; Documento generado el {{ now()->format('d-m-Y H:i') }}
    </div>
</body>

</html>
