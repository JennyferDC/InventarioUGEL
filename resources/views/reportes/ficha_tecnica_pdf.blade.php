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
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #8B0000;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #8B0000; /* ugel-guinda */
            margin: 0 0 5px 0;
            font-size: 26px;
        }
        .header h2 {
            margin: 0;
            color: #333;
            font-size: 18px;
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

    <div class="header">
        <h1>Inventario UGEL</h1>
        <h2>Ficha Técnica de Equipo</h2>
        <p style="color: #666; margin-top: 5px; font-size: 11px;">Impreso el: {{ date('d/m/Y H:i') }}</p>
    </div>

    <div class="section-title">Información General</div>
    <table>
        <tbody>
            <tr>
                <th>Código Informática</th>
                <td><strong>{{ $equipo->cod_informatica }}</strong></td>
            </tr>
            <tr>
                <th>Tipo de Equipo</th>
                <td>{{ $equipo->tipo }}</td>
            </tr>
            <tr>
                <th>Estado Actual</th>
                <td>{{ $equipo->estado }}</td>
            </tr>
            <tr>
                <th>Fecha Ingreso</th>
                <td>{{ $equipo->fecha_ingreso ? date('d/m/Y', strtotime($equipo->fecha_ingreso)) : 'No registrada' }}</td>
            </tr>
            <tr>
                <th>Vida Útil Estimada</th>
                <td>{{ $equipo->vida_util_anios ? $equipo->vida_util_anios . ' años' : 'No especificada' }}</td>
            </tr>
            <tr>
                <th>Responsable Asignado</th>
                <td>{{ $equipo->persona ? $equipo->persona->nombre_completo : 'Sin asignar' }}</td>
            </tr>
            <tr>
                <th>Área del Responsable</th>
                <td>{{ ($equipo->persona && $equipo->persona->area) ? $equipo->persona->area->nombre : '-' }}</td>
            </tr>
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
    
    <div style="margin-top: 50px; text-align: center;">
        <p>______________________________________</p>
        <p style="margin:0; font-size: 12px; font-weight: bold;">Firma del Responsable de TI</p>
    </div>

    <div class="footer">
        Sistema de Inventario UGEL - {{ date('Y') }}
    </div>

</body>
</html>
