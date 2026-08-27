<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #1f2937;
        }

        .header {
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .logo {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a5f;
        }

        .logo .sur {
            color: #d97706;
        }

        .titulo {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a5f;
            margin-top: 6px;
        }

        .subtitulo {
            color: #6b7280;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        thead th {
            background-color: #1e3a5f;
            color: #ffffff;
            text-align: left;
            padding: 5px 6px;
            font-size: 9px;
            text-transform: uppercase;
        }

        tbody td {
            padding: 5px 6px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: top;
        }

        tbody tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        .num {
            text-align: right;
        }

        .totales {
            margin-top: 14px;
            width: 300px;
            margin-left: auto;
        }

        .totales td {
            padding: 4px 6px;
        }

        .totales .etq {
            color: #6b7280;
        }

        .totales .val {
            text-align: right;
            font-weight: bold;
        }

        .footer {
            margin-top: 18px;
            color: #9ca3af;
            font-size: 8px;
        }

        .vacio {
            margin-top: 20px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <span class="logo">Leufu<span class="sur">Sur</span> SpA</span>
        <div class="titulo">{{ $titulo }}</div>
        <div class="subtitulo">{{ $subtitulo }} · Generado el {{ now()->format('d-m-Y H:i') }}</div>
    </div>

    @if (count($filas) > 0)
        @php
            // Una columna es numérica si su primera celda de datos empieza con "$".
            $primera = $filas[0] ?? [];
            $numerica = fn ($i) => isset($primera[$i]) && str_starts_with((string) $primera[$i], '$');
        @endphp
        <table>
            <thead>
                <tr>
                    @foreach ($columnas as $i => $columna)
                        <th @class(['num' => $numerica($i)]) style="{{ $i === 0 ? 'width:70px;' : '' }}">{{ $columna }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $fila)
                    <tr>
                        @foreach ($fila as $i => $valor)
                            <td @class(['num' => str_starts_with((string) $valor, '$')])>{{ $valor }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if (! empty($totales))
            <table class="totales">
                @foreach ($totales as $etiqueta => $valor)
                    <tr>
                        <td class="etq">{{ $etiqueta }}</td>
                        <td class="val">{{ $valor }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    @else
        <p class="vacio">No hay datos para el período seleccionado.</p>
    @endif

    <div class="footer">Leufu Sur SpA — Informe generado desde el portal de gestión.</div>
</body>

</html>
