<!DOCTYPE html>
<html>

<head>
    <title>Cycle Time Report</title>
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

        .summary-box {
            border: 1px solid #333;
            padding: 10px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
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

        .status-tinggi {
            color: #dc2626;
            font-weight: bold;
        }

        .status-rendah {
            color: #d97706;
            font-weight: bold;
        }

        .status-normal {
            color: #4b5563;
        }

        .pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 7pt;
            color: #888;
            border-top: 1px solid #ccc;
            padding-top: 3px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>Cycle Time Report</h2>
        <p>Tanggal: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} -
            {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
        </p>
    </div>

    <div class="summary-box">
        <table style="border: none; width: 100%;">
            <tr style="border: none;">
                <td style="border: none; width: 50%;">
                    <strong>Nama Produk:</strong><br>
                    <span style="font-size: 11pt;">{{ $selectedItem->name ?? 'Unknown item' }}</span><br>
                    <span style="color: #666;">({{ $itemCode }})</span>
                </td>
                <td style="border: none; text-align: center;">
                    <strong>Total Riwayat Input:</strong><br>
                    <span style="font-size: 14pt;">{{ number_format($totalData) }} Data</span>
                </td>
                <td style="border: none; text-align: right;">
                    @php
                        $avgSec = round($averageCycleTimeSec);
                        $avgMin = floor($avgSec / 60);
                        $avgRemSec = $avgSec % 60;
                    @endphp
                    <strong>Rata-rata Global:</strong><br>
                    <span style="font-size: 14pt;">{{ $avgMin }}m {{ $avgRemSec }}s</span>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%">Tanggal Input</th>
                <th style="width: 25%">Operator</th>
                <th style="width: 25%">Mesin</th>
                <th style="width: 10%">Hasil (PCS)</th>
                <th style="width: 10%">Cycle Time</th>
                <th style="width: 15%">Status V.S Rata2</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $rowMins = floor($row->cycle_time_used_sec / 60);
                    $rowSecs = $row->cycle_time_used_sec % 60;

                    // Anomaly check
                    $diffPercent = 0;
                    $isAnomalyHigh = false;
                    $isAnomalyLow = false;
                    if ($averageCycleTimeSec > 0) {
                        $diffPercent = (($row->cycle_time_used_sec - $averageCycleTimeSec) / $averageCycleTimeSec) * 100;
                        if ($diffPercent > 20)
                            $isAnomalyHigh = true;
                        if ($diffPercent < -20)
                            $isAnomalyLow = true;
                    }

                    $statusClass = 'status-normal';
                    $statusText = 'Normal';
                    if ($isAnomalyHigh) {
                        $statusClass = 'status-tinggi';
                        $statusText = '+' . number_format($diffPercent, 1) . '% Tinggi';
                    } elseif ($isAnomalyLow) {
                        $statusClass = 'status-rendah';
                        $statusText = number_format($diffPercent, 1) . '% Rendah';
                    }
                @endphp
                <tr>
                    <td class="text-center">
                        {{ \Carbon\Carbon::parse($row->production_date)->format('d/m/Y') }}<br>
                        <span style="font-size: 7.5pt; color: #666;">Shift {{ $row->shift }}</span>
                    </td>
                    <td>
                        <strong>{{ $row->operator->name ?? $row->operator_code }}</strong><br>
                        <span style="font-size: 7.5pt; color: #666;">{{ $row->operator_code }}</span>
                    </td>
                    <td>
                        <strong>{{ $row->machine->name ?? $row->machine_code }}</strong><br>
                        <span style="font-size: 7.5pt; color: #666;">{{ $row->machine_code }}</span>
                    </td>
                    <td class="text-right"><strong>{{ $row->actual_qty }}</strong></td>
                    <td class="text-right {{ $statusClass }}">{{ $rowMins }}m {{ $rowSecs }}s</td>
                    <td class="text-center">
                        <span class="{{ $statusClass }}">{{ $statusText }}</span>
                        @if($row->remark)
                            <div
                                style="margin-top: 4px; font-size: 7.5pt; color: #555; text-align: left; background-color: #f9f9f9; padding: 2px;">
                                <strong>Ket:</strong> {{ $row->remark }}
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">
                        Data riwayat tidak ditemukan untuk barang dan rentang tanggal ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pdf-footer">
        IP: {{ request()->ip() }} &nbsp;|&nbsp;
        User: {{ auth()->user()->name ?? 'Guest' }} &nbsp;|&nbsp;
        Digenerate: {{ \Carbon\Carbon::now('Asia/Jakarta')->format('d/m/Y H:i:s') }}
    </div>

</body>

</html>