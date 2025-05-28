<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Segmentação RFM</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
    <h2>Segmentação RFM</h2>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Segmento</th>
                <th>Recência</th>
                <th>Frequência</th>
                <th>Monetário</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clientes as $cliente)
            <tr>
                <td>{{ $cliente['Nome'] ?? '-' }}</td>
                <td>{{ $cliente['Segmento'] ?? '-' }}</td>
                <td>{{ $cliente['Recência'] ?? '-' }}</td>
                <td>{{ $cliente['Frequência'] ?? '-' }}</td>
                <td>{{ number_format($cliente['Monetário'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>