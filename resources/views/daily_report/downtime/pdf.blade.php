<!DOCTYPE html>
<html>

<head>
    <title>Laporan Harian Downtime</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 5px;
            vertical-align: top;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Laporan Harian Downtime</h2>
        <p>Tanggal: {{ \Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20%">Mesin</th>
                <th style="width: 20%">Operator</th>
                <th style="width: 15%">Durasi (Min)</th>
                <th style="width: 45%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->machine->name ?? $row->machine_code }}</td>
                    <td>{{ $row->operator->name ?? $row->operator_code }}</td>
                    <td class="text-center">{{ $row->duration_minutes }}</td>
                    <td>{{ $row->note ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>