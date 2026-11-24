<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo ?? 'Reporte General de Equipos' }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 10mm 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            margin-bottom: 15px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none !important;
            margin-top: 0 !important;
            table-layout: auto !important;
        }
        .header-table td {
            border: none !important;
            padding: 0 !important;
            vertical-align: middle;
        }
        .header-logo {
            width: 160px;
            text-align: left;
        }
        .header-logo img {
            max-height: 52px;
            max-width: 150px;
            height: auto;
        }
        .header-titles {
            text-align: center;
        }
        .header-titles h1 {
            color: #8B0000; /* ugel-guinda */
            margin: 0;
            font-size: 20px;
        }
        .header-titles h2 {
            margin: 4px 0;
            color: #1e293b;
            font-size: 13px;
        }
        .header-titles p {
            margin: 2px 0;
            color: #64748b;
            font-size: 9px;
        }
        .header-spacer {
            width: 160px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
            word-wrap: break-word;
            vertical-align: top;
            font-size: 9px;
        }
        th {
            background-color: #0f172a; /* ugel-azul */
            color: #fff;
            font-size: 8.5px;
            text-transform: uppercase;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 15px;
            text-align: right;
            font-size: 8.5px;
            color: #777;
        }
    </style>
</head>
<body>
@php
    $logoBase64 = '';
    $possiblePaths = [
        public_path('images/logo_ugel.png'),
        public_path('logo_ugel.png'),
        'C:/Users/WilsonE/Documents/Ugel-huanuco.png',
        'C:/Users/WilsonE/Documents/Ugel-huanuco',
    ];
    foreach ($possiblePaths as $path) {
        if (file_exists($path) && is_file($path)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($path));
            break;
        }
    }
@endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Logo UGEL Huánuco">
                    @endif
                </td>
                <td class="header-titles">
                    <h1>Inventario UGEL</h1>
                    <h2>{{ $titulo ?? 'Reporte General de Equipos' }}</h2>
                    <p>Fecha de generación: {{ date('d/m/Y H:i:s') }}</p>
                </td>
                <td class="header-spacer"></td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 16%;">Código</th>
                <th style="width: 16%;">Tipo</th>
                <th style="width: 12%;" class="text-center">Estado</th>
                <th style="width: 12%;" class="text-center">F. Ingreso</th>
                <th style="width: 12%;" class="text-center">Vida Útil</th>
                <th style="width: 32%;">Observación Técnica de Baja</th>
            </tr>
        </thead>
        <tbody>
            @forelse($equipos as $equipo)
            @php
                $observacionBaja = !empty($equipo->observacion_tecnica) ? trim($equipo->observacion_tecnica) : '';
            @endphp
            <tr>
                <td><strong>{{ $equipo->cod_informatica }}</strong></td>
                <td>{{ $equipo->tipo }}</td>
                <td class="text-center">{{ $equipo->estado }}</td>
                <td class="text-center">{{ $equipo->fecha_ingreso ? date('d/m/Y', strtotime($equipo->fecha_ingreso)) : 'S/R' }}</td>
                <td class="text-center">{{ $equipo->vida_util_anios ? $equipo->vida_util_anios . ' años' : '-' }}</td>
                <td>{{ $observacionBaja }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No se encontraron equipos para los criterios seleccionados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado por Sistema de Inventario UGEL.
    </div>

</body>
</html>
