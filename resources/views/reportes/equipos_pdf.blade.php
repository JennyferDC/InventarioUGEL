<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo ?? 'Reporte General de Equipos' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #8B0000; /* ugel-guinda */
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #0f172a; /* ugel-azul */
            color: #fff;
            font-size: 11px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .badge {
            background-color: #eee;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Inventario UGEL</h1>
        <h2>{{ $titulo ?? 'Reporte General de Equipos' }}</h2>
        <p>Fecha de generación: {{ date('d/m/Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>F. Ingreso</th>
                <th>Vida Útil</th>
                <th>Responsable</th>
                <th>Área</th>
            </tr>
        </thead>
        <tbody>
            @forelse($equipos as $equipo)
            <tr>
                <td><strong>{{ $equipo->cod_informatica }}</strong></td>
                <td>{{ $equipo->tipo }}</td>
                <td>{{ $equipo->estado }}</td>
                <td>{{ $equipo->fecha_ingreso ? date('d/m/Y', strtotime($equipo->fecha_ingreso)) : 'S/R' }}</td>
                <td class="text-center">{{ $equipo->vida_util_anios ?? '-' }}</td>
                <td>{{ $equipo->persona ? $equipo->persona->nombre_completo : 'Sin asignar' }}</td>
                <td>{{ ($equipo->persona && $equipo->persona->area) ? $equipo->persona->area->nombre : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">No se encontraron equipos para los tipos seleccionados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado por Sistema de Inventario UGEL. Página 1.
    </div>

</body>
</html>
