<!DOCTYPE html>
<html>

<head>
    <title>Laporan KPI Harian Mesin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h2 {
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px 6px;
            vertical-align: middle;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .kpi-good {
            color: #166534;
            font-weight: bold;
        }

        .kpi-bad {
            color: #dc2626;
            font-weight: bold;
        }

        .kpi-mid {
            color: #d97706;
            font-weight: bold;
        }

        /* Layout for Signatures */
        .signatures {
            margin-top: 30px;
            width: 100%;
            border: none;
        }

        .signatures td {
            border: none;
            text-align: center;
            vertical-align: top;
            width: 25%;
            padding-top: 50px;
        }

        .sign-title {
            font-weight: bold;
            margin-bottom: 60px;
            display: block;
        }

        .sign-name {
            border-top: 1px solid #333;
            display: inline-block;
            width: 80%;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>Laporan KPI Harian Mesin</h2>
        <p>Tanggal: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">SF</th>
                <th style="width: 10%">Mesin</th>
                <th style="width: 20%">Operator</th>
                <th style="width: 30%">Item & Size</th>
                <th style="width: 10%">Jam</th>
                <th style="width: 10%">Target</th>
                <th style="width: 10%">Aktual</th>
                <th style="width: 10%">KPI</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="text-center">{{ $row->shift }}</td>
                    <td class="text-center">
                        {{ $row->machine_code }}
                    </td>
                    <td>
                        {{ $row->operator->name ?? $row->operator_code }}
                    </td>
                    <td>
                        {{ $row->item->name ?? $row->item_code }}
                        @if($row->size) <span style="color: #666; font-size: 8pt;">({{ $row->size }})</span> @endif
                    </td>
                    <td class="text-right">
                        {{ number_format($row->work_hours, 2) }}
                    </td>
                    <td class="text-right">
                        {{ $row->target_qty }}
                    </td>
                    <td class="text-right">
                        {{ $row->actual_qty }}
                    </td>
                    <td class="text-center">
                        @php
                            $class = 'kpi-bad';
                            if ($row->achievement_percent >= 100)
                                $class = 'kpi-good';
                            elseif ($row->achievement_percent >= 85)
                                $class = 'kpi-mid';
                        @endphp
                        <span class="{{ $class }}">
                            {{ $row->achievement_percent }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">
                        Data tidak ditemukan untuk tanggal ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Signature Section -->
    <table class="signatures">
        <tr>
            <td>
                <span class="sign-title">Admin</span>
                <span class="sign-name">( ....................... )</span>
            </td>
            <td>
                <span class="sign-title">SPV Shift 1</span>
                <span class="sign-name">( ....................... )</span>
            </td>
            <td>
                <span class="sign-title">SPV Shift 2</span>
                <span class="sign-name">( ....................... )</span>
            </td>
            <td>
                <span class="sign-title">SPV Shift 3</span>
                <span class="sign-name">( ....................... )</span>
            </td>
        </tr>
    </table>

</body>

</html>