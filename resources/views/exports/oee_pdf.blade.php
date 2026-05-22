<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan OEE</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
            color: #333;
        }
        @page {
            size: A4 landscape;
            margin: 20px;
        }
        .header {
            margin-bottom: 15px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 5px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        .data-table th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .font-bold {
            font-weight: bold;
        }
        .bg-gray-100 {
            background-color: #f3f4f6;
        }
        .bg-blue-50 {
            background-color: #eff6ff;
        }
        .text-red {
            color: #b91c1c;
        }
        .text-amber {
            color: #b45309;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            font-size: 9px;
            color: #666;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">LAPORAN ANALISIS OEE (OVERALL EQUIPMENT EFFECTIVENESS)</div>
        <table class="meta-table">
            <tr>
                <td width="15%"><strong>Departemen:</strong></td>
                <td width="35%">{{ $departmentCode }}</td>
                <td width="15%"><strong>Periode:</strong></td>
                <td width="35%">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</td>
            </tr>
            <tr>
                <td><strong>Scope Mesin:</strong></td>
                <td>{{ $selectedMachine ?: 'Semua Mesin' }}</td>
                <td><strong>Generated At:</strong></td>
                <td>{{ $generated_at }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th class="text-right">Total Runtime Mesin (Jam)</th>
                <th class="text-right">Downtime (Jam)</th>
                <th class="text-right">Target Qty</th>
                <th class="text-right">Aktual Qty</th>
                <th class="text-right">Reject Qty</th>
                <th class="text-center">Availability (%)</th>
                <th class="text-center">Performance (%)</th>
                <th class="text-center">Quality (%)</th>
                <th class="text-center">OEE (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format($row['work_hours'], 2) }}</td>
                    <td class="text-right text-amber">{{ number_format($row['downtime_hours'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['target_qty']) }}</td>
                    <td class="text-right">{{ number_format($row['actual_qty']) }}</td>
                    <td class="text-right text-red font-bold">{{ number_format($row['reject_qty']) }}</td>
                    <td class="text-center bg-gray-100">{{ number_format($row['availability'] * 100, 2) }}%</td>
                    <td class="text-center bg-gray-100">{{ number_format($row['performance'] * 100, 2) }}%</td>
                    <td class="text-center bg-gray-100">{{ number_format($row['quality'] * 100, 2) }}%</td>
                    <td class="text-center bg-blue-50 font-bold">{{ number_format($row['oee'] * 100, 2) }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-bold bg-gray-100">
                <td>TOTAL / RINGKASAN PERIODE</td>
                <td class="text-right">{{ number_format($summary['work_hours'], 2) }}</td>
                <td class="text-right text-amber">{{ number_format($summary['downtime_hours'], 2) }}</td>
                <td class="text-right">{{ number_format($summary['target_qty']) }}</td>
                <td class="text-right">{{ number_format($summary['actual_qty']) }}</td>
                <td class="text-right text-red">{{ number_format($summary['reject_qty']) }}</td>
                <td class="text-center bg-gray-100">{{ number_format($summary['availability'] * 100, 2) }}%</td>
                <td class="text-center bg-gray-100">{{ number_format($summary['performance'] * 100, 2) }}%</td>
                <td class="text-center bg-gray-100">{{ number_format($summary['quality'] * 100, 2) }}%</td>
                <td class="text-center bg-blue-50">{{ number_format($summary['oee'] * 100, 2) }}%</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generated By : {{ $generated_by }} | Generated At : {{ $generated_at }} | IP Address : {{ $generated_ip }}
    </div>
</body>
</html>
