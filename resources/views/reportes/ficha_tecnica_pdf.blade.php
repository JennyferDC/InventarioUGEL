<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha Técnica de Equipo - {{ $equipo->cod_informatica }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #333;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #8B0000;
            padding-bottom: 15px;
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
            max-height: 55px;
            max-width: 125px;
            height: auto;
        }
        .header-titles {
            text-align: center;
        }
        .header-titles h1 {
            color: #8B0000; /* ugel-guinda */
            margin: 0 0 5px 0;
            font-size: 26px;
        }
        .header-titles h2 {
            margin: 0;
            color: #333;
            font-size: 18px;
        }
        .header-spacer {
            width: 130px;
        }
        .section-title {
            background-color: #0f172a; /* ugel-azul */
            color: #fff;
            padding: 5px 10px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            width: 30%;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
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
                    <h2>Ficha Técnica de Equipo</h2>
                    <p style="color: #666; margin-top: 5px; font-size: 11px;">Impreso el: {{ date('d/m/Y H:i') }}</p>
                </td>
                <td class="header-spacer"></td>
            </tr>
        </table>
    </div>

    <div class="section-title">Información General</div>
    <table>
        <tbody>
            <tr>
                <th>Código Informática</th>
                <td><strong>{{ $equipo->cod_informatica }}</strong></td>
            </tr>
            @if($equipo->cod_patrimonial)
            <tr>
                <th>Código Patrimonial</th>
                <td>{{ $equipo->cod_patrimonial }}</td>
            </tr>
            @endif
            @if($equipo->cod_serial)
            <tr>
                <th>Número de Serie</th>
                <td>{{ $equipo->cod_serial }}</td>
            </tr>
            @endif
            <tr>
                <th>Tipo de Equipo</th>
                <td>{{ $equipo->tipo }}</td>
            </tr>
            <tr>
                <th>Estado Actual</th>
                <td>{{ $equipo->estado }}</td>
            </tr>
            @if(strtoupper($equipo->estado ?? '') === 'BAJA')
            <tr>
                <th>Observación Técnica de Baja</th>
                <td>{{ $equipo->observacion_tecnica ?? '' }}</td>
            </tr>
            @endif
            <tr>
                <th>Fecha Ingreso</th>
                <td>{{ $equipo->fecha_ingreso ? date('d/m/Y', strtotime($equipo->fecha_ingreso)) : 'No registrada' }}</td>
            </tr>
            <tr>
                <th>Vida Útil Estimada</th>
                <td>{{ $equipo->vida_util_anios ? $equipo->vida_util_anios . ' años' : 'No especificada' }}</td>
            </tr>
            @if(!empty($filtros['incluir_datos_responsable']))
            <tr>
                <th>Responsable Asignado</th>
                <td>{{ $equipo->persona ? $equipo->persona->nombre_completo : 'Sin asignar' }}</td>
            </tr>
            <tr>
                <th>Área del Responsable</th>
                <td>{{ ($equipo->persona && $equipo->persona->oficina && $equipo->persona->oficina->area) ? $equipo->persona->oficina->area->nombre : (($equipo->persona && $equipo->persona->area) ? $equipo->persona->area->nombre : '-') }}</td>
            </tr>
            @if($equipo->persona && $equipo->persona->celular)
            <tr>
                <th>Celular del Responsable</th>
                <td>{{ $equipo->persona->celular }}</td>
            </tr>
            @endif
            @endif
        </tbody>
    </table>

    <div class="section-title">Características Técnicas</div>
    <table>
        <tbody>
            @forelse($equipo->caracteristicas as $caracteristica)
            <tr>
                <th>{{ $caracteristica->clave }}</th>
                <td>{{ $caracteristica->valor }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="2" style="text-align: center; color: #666; padding: 15px;">No se registraron características técnicas para este equipo.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="section-title">Programas y Software Instalado</div>
    <table>
        <thead>
            <tr>
                <th style="width: 30%; background-color: #f2f2f2;">Código</th>
                <th style="width: 45%; background-color: #f2f2f2;">Nombre del Programa</th>
                <th style="width: 25%; background-color: #f2f2f2;">Tipo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($equipo->programas as $programa)
            <tr>
                <td style="font-family: monospace;">{{ $programa->cod_informatica }}</td>
                <td>{{ $programa->nombre }}</td>
                <td>{{ $programa->tipo ? ucfirst($programa->tipo) : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center; color: #666; padding: 15px;">No hay programas de software asignados a este equipo.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    @if(!empty($filtros['incluir_otros_equipos']) && count($otros_equipos) > 0)
    <div class="section-title" style="margin-top: 20px;">Otros Equipos Asignados a la Persona</div>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Tipo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($otros_equipos as $otro)
            <tr>
                <td>{{ $otro->cod_informatica }}</td>
                <td>{{ $otro->tipo }}</td>
                <td>{{ $otro->estado }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    
    <div style="margin-top: 50px; text-align: center;">
        <p>______________________________________</p>
        <p style="margin:0; font-size: 12px; font-weight: bold;">Firma del Responsable de TI</p>
    </div>

    <div class="footer">
        Sistema de Inventario UGEL - {{ date('Y') }}
    </div>

</body>
</html>
