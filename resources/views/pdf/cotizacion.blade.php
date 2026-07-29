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
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        {{-- Logotipo de texto: reemplazar por <img> cuando se disponga del archivo del logo. --}}
        .logo-leufu {
            font-size: 22px;
            font-weight: bold;
            color: #1e3a5f;
        }

        .logo-sur {
            color: #d97706;
        }

        .logo-tagline {
            color: #d97706;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .cotizacion-titulo {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
            color: #1e3a5f;
        }

        .cotizacion-id {
            text-align: right;
            color: #6b7280;
            font-size: 10px;
        }

        .seccion-header {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 5px 10px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .seccion-caja {
            border: 1px solid #e5e7eb;
            border-top: none;
            padding: 10px;
            margin-bottom: 16px;
        }

        .datos-tabla {
            width: 100%;
        }

        .datos-tabla td {
            padding: 2px 0;
            vertical-align: top;
        }

        .datos-label {
            color: #6b7280;
            width: 110px;
        }

        table.lineas {
            width: 100%;
            border-collapse: collapse;
        }

        table.lineas th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 6px 8px;
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }

        table.lineas td {
            padding: 6px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }

        .num {
            text-align: right;
        }

        .detalle-extendido {
            margin-top: 4px;
            font-size: 9px;
            color: #6b7280;
        }

        table.resumen {
            width: 260px;
            margin-left: auto;
            margin-top: 10px;
        }

        table.resumen td {
            padding: 3px 8px;
        }

        table.resumen .total td {
            background-color: #1e3a5f;
            color: #ffffff;
            font-weight: bold;
            font-size: 13px;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .footer {
            margin-top: 16px;
            color: #6b7280;
            font-size: 9px;
            font-style: italic;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td width="55%">
                <span class="logo-leufu">Leufu<span class="logo-sur">Sur</span></span>
                <div class="logo-tagline">Soluciones Informáticas</div>
            </td>
            <td width="45%">
                <div class="cotizacion-titulo">COTIZACIÓN</div>
                <div class="cotizacion-id">ID : {{ $cotizacion->numero_cotizacion }}</div>
            </td>
        </tr>
    </table>

    {{-- Datos del cliente --}}
    <div class="seccion-header">Datos del Cliente</div>
    <div class="seccion-caja">
        <table class="datos-tabla">
            <tr>
                <td width="55%">
                    <table class="datos-tabla">
                        <tr>
                            <td class="datos-label">Unidad Compradora</td>
                            <td>{{ $cotizacion->cliente->nombre_empresa ?: $cotizacion->cliente->nombre }}</td>
                        </tr>
                        <tr>
                            <td class="datos-label">RUT</td>
                            <td>{{ $cotizacion->cliente->rut_run }}</td>
                        </tr>
                        <tr>
                            <td class="datos-label">Giro</td>
                            <td>{{ $cotizacion->cliente->giro ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="datos-label">Dirección</td>
                            <td>{{ $cotizacion->cliente->direccion ?: '—' }}</td>
                        </tr>
                    </table>
                </td>
                <td width="45%">
                    <table class="datos-tabla">
                        <tr>
                            <td class="datos-label">Fecha</td>
                            <td>{{ $cotizacion->fecha_emision->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="datos-label">Validez</td>
                            <td>{{ $cotizacion->fecha_vencimiento->format('d/m/Y') }}</td>
                        </tr>
                        @if ($cotizacion->id_mercado_publico)
                            <tr>
                                <td class="datos-label">ID Mercado Público</td>
                                <td>{{ $cotizacion->id_mercado_publico }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- Detalle de productos cotizados --}}
    <div class="seccion-header">Detalle de Productos Cotizados</div>
    <div class="seccion-caja">
        <table class="lineas">
            <thead>
                <tr>
                    <th width="6%">Ítem</th>
                    <th width="8%" class="num">Cant.</th>
                    <th width="10%">Unidad</th>
                    <th>Detalle</th>
                    <th width="14%" class="num">Unitario</th>
                    <th width="14%" class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cotizacion->lineas as $indice => $linea)
                    <tr>
                        <td>{{ $indice + 1 }}</td>
                        <td class="num">{{ rtrim(rtrim($linea->cantidad, '0'), '.') }}</td>
                        <td>{{ $linea->unidad }}</td>
                        <td>
                            {{ $linea->descripcion }}
                            @if ($linea->detalle_extendido)
                                <div class="detalle-extendido">{!! $linea->detalle_extendido !!}</div>
                            @endif
                        </td>
                        <td class="num">${{ number_format($linea->valor_unitario, 0, ',', '.') }}</td>
                        <td class="num">${{ number_format($linea->subtotal_calculado, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="resumen">
            <tr>
                <td>Total Neto</td>
                <td class="num">${{ number_format($cotizacion->base_gravada_calculada, 0, ',', '.') }}</td>
            </tr>
            @if ((float) $cotizacion->descuento_porcentaje > 0)
                <tr>
                    <td>Descuento ({{ rtrim(rtrim($cotizacion->descuento_porcentaje, '0'), '.') }}%)</td>
                    <td class="num">-${{ number_format($cotizacion->subtotal_calculado - $cotizacion->base_gravada_calculada, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr>
                <td>IVA (19%)</td>
                <td class="num">${{ number_format($cotizacion->iva_calculado, 0, ',', '.') }}</td>
            </tr>
            <tr class="total">
                <td>TOTAL</td>
                <td class="num">${{ number_format($cotizacion->total_calculado, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Datos de la empresa --}}
    <div class="seccion-header">Datos de la Empresa</div>
    <div class="seccion-caja">
        <table class="datos-tabla">
            <tr>
                <td class="datos-label">Razón Social</td>
                <td>Leufu Sur SpA</td>
            </tr>
            <tr>
                <td class="datos-label">RUT</td>
                <td>77.004.815-K</td>
            </tr>
            <tr>
                <td class="datos-label">Giro</td>
                <td>Servicios y Soluciones Tecnológicas</td>
            </tr>
            <tr>
                <td class="datos-label">Dirección</td>
                <td>Luis Sagardia #66 — Lebu</td>
            </tr>
            <tr>
                <td class="datos-label">Correo</td>
                <td>leufusurspa@icloud.com</td>
            </tr>
            <tr>
                <td class="datos-label">Teléfono</td>
                <td>+56 9 6247 1305</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Validez de la oferta: {{ $cotizacion->fecha_vencimiento->format('d/m/Y') }}
    </div>
</body>

</html>
