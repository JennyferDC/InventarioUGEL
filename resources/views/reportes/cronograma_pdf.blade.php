<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cronograma de Mantenimiento - {{ $nombreOficina }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 12mm 12mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.3;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #8B0000;
            padding-bottom: 12px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none !important;
            margin: 0 !important;
        }
        .header-table td {
            border: none !important;
            padding: 0 !important;
            vertical-align: middle;
        }
        .header-logo {
            width: 130px;
            text-align: left;
        }
        .header-logo img {
            max-height: 52px;
            max-width: 125px;
            height: auto;
        }
        .header-titles {
            text-align: center;
        }
        .header-titles h1 {
            color: #8B0000; /* ugel-guinda */
            margin: 0;
            font-size: 19px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-titles h2 {
            margin: 3px 0;
            color: #1e293b;
            font-size: 13px;
        }
        .header-titles p {
            margin: 2px 0 0 0;
            color: #64748b;
            font-size: 9px;
        }
        .header-spacer {
            width: 130px;
        }

        /* Card de metadatos del reporte */
        .card-info {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 15px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            border: none;
            padding: 3px 4px;
            font-size: 9.5px;
            vertical-align: top;
        }
        .info-label {
            color: #475569;
            font-weight: bold;
            width: 22%;
        }
        .info-value {
            color: #0f172a;
            width: 28%;
        }

        /* Tabla de actividades */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
            word-wrap: break-word;
            vertical-align: middle;
            font-size: 9px;
        }
        .data-table th {
            background-color: #0f172a; /* ugel-azul */
            color: #ffffff;
            font-size: 8.5px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }

        /* Badges de estado */
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-encurso {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .status-finalizado, .status-completado {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .status-pendiente {
            background-color: #e0f2fe;
            color: #075985;
            border: 1px solid #bae6fd;
        }
        .status-cancelado {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        /* Firmas y pie de página */
        .signatures {
            margin-top: 45px;
            page-break-inside: avoid;
        }
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            border: none;
            text-align: center;
            vertical-align: top;
            padding: 0;
        }
        .sign-line {
            border-top: 1px solid #475569;
            width: 75%;
            margin: 0 auto 5px auto;
        }
        .sign-title {
            font-weight: bold;
            font-size: 9px;
            color: #1e293b;
        }
        .sign-sub {
            font-size: 8px;
            color: #64748b;
        }

        .footer {
            margin-top: 25px;
            text-align: right;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
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
                    <h1>UGEL Huánuco</h1>
                    <h2>Cronograma de Mantenimiento Informático</h2>
                    <p>Fecha de emisión: {{ date('d/m/Y H:i:s') }}</p>
                </td>
                <td class="header-spacer"></td>
            </tr>
        </table>
    </div>

    <!-- Metadatos requeridos: Nombre del plan, Año, Unidad Funcional y Oficina -->
    <div class="card-info">
        <table class="info-table">
            <tr>
                <td class="info-label">Nombre del Plan:</td>
                <td class="info-value"><strong>{{ $nombrePlan }}</strong></td>
                <td class="info-label">Año:</td>
                <td class="info-value"><strong>{{ $anio }}</strong></td>
            </tr>
            <tr>
                <td class="info-label">Unidad Funcional:</td>
                <td class="info-value"><strong>{{ $nombreArea }}</strong></td>
                <td class="info-label">Oficina:</td>
                <td class="info-value"><strong>{{ $nombreOficina }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Tabla de actividades programadas -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">N°</th>
                @if($mostrarColumnaOficina)
                <th style="width: 22%;">Oficina</th>
                @endif
                <th style="{{ $mostrarColumnaOficina ? 'width: 41%;' : 'width: 55%;' }}">Actividad</th>
                <th style="width: 11%; text-align: center;">F. Inicio</th>
                <th style="width: 11%; text-align: center;">F. Fin</th>
                <th style="width: 9%; text-align: center;">Días</th>
                <th style="width: 12%; text-align: center;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                @if($mostrarColumnaOficina)
                <td><strong>{{ $item->oficina->nombre ?? 'N/A' }}</strong></td>
                @endif
                <td>{{ $item->actividad }}</td>
                <td class="text-center">{{ $item->fecha_inicio ? date('d/m/Y', strtotime($item->fecha_inicio)) : 'N/A' }}</td>
                <td class="text-center">{{ $item->fecha_fin ? date('d/m/Y', strtotime($item->fecha_fin)) : 'N/A' }}</td>
                <td class="text-center">{{ $item->dias }}</td>
                <td class="text-center">
                    <span class="status-badge {{ $item->estado_class }}">
                        {{ $item->estado_display }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $mostrarColumnaOficina ? 7 : 6 }}" class="text-center" style="padding: 15px; color: #64748b;">
                    No se encontraron actividades registradas para la selección realizada.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Bloque de firmas -->
    <div class="signatures">
        <table>
            <tr>
                <td style="width: 45%;">
                    <div class="sign-line"></div>
                    <div class="sign-title">Responsable de Informática / TI</div>
                    <div class="sign-sub">UGEL Huánuco</div>
                </td>
                <td style="width: 10%;">&nbsp;</td>
                <td style="width: 45%;">
                    <div class="sign-line"></div>
                    <div class="sign-title">Responsable de Oficina</div>
                    <div class="sign-sub">{{ $mostrarColumnaOficina ? 'Oficina(s) Responsable(s)' : $nombreOficina }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Sistema de Gestión de Inventario UGEL Huánuco &bull; Generado el {{ date('d/m/Y H:i:s') }}
    </div>

</body>
</html>
