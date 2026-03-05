<!DOCTYPE html>
<html>

<head>
    <title>Laporan KPI Harian Operator</title>
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

        th, td {
            border: 1px solid #333;
            padding: 4px 6px;
            vertical-align: middle;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }

        .kpi-good { color: #166534; font-weight: bold; }
        .kpi-bad  { color: #dc2626; font-weight: bold; }
        .kpi-mid  { color: #d97706; font-weight: bold; }

        .signatures { margin-top: 30px; width: 100%; border: none; }
        .signatures td {
            border: none;
            text-align: center;
            vertical-align: top;
            width: 50%;
            padding-top: 50px;
        }
        .sign-title { font-weight: bold; margin-bottom: 60px; display: block; }
        .sign-name {
            border-top: 1px solid #333;
            display: inline-block;
            width: 80%;
            padding-top: 5px;
        }

        .pdf-footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
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
        <h2>Laporan KPI Harian Operator</h2>
        <p>Tanggal: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 7%">Tgl</th>
                <th style="width: 4%">SF</th>
                <th style="width: 18%">Operator</th>
                <th style="width: 9%">Mesin</th>
                <th style="width: 9%">Heat No</th>
                <th style="width: 25%">Item &amp; Size</th>
                <th style="width: 7%">Jam</th>
                <th style="width: 6%">Target</th>
                <th style="width: 6%">Aktual</th>
                <th style="width: 9%">KPI</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $tgl      = $row->production_date;
                    $dailyKpi = $dailyKpiMap[$tgl] ?? null;
                    $dailyPct = $dailyKpi ? $dailyKpi->kpi_percent : null;

                    // Warna KPI per pekerjaan
                    $class = 'kpi-bad';
                    if ($row->achievement_percent >= 100)     $class = 'kpi-good';
                    elseif ($row->achievement_percent >= 85)  $class = 'kpi-mid';

                    // Warna rata2 harian
                    $avgClass = 'kpi-bad';
                    if ($dailyPct >= 100)    $avgClass = 'kpi-good';
                    elseif ($dailyPct >= 85) $avgClass = 'kpi-mid';
                @endphp
                <tr>
                    <td class="text-center">{{ \Carbon\Carbon::parse($tgl)->format('j-M') }}</td>
                    <td class="text-center">{{ $row->shift }}</td>
                    <td>{{ $operatorNames[$row->operator_code] ?? $row->operator_code }}</td>
                    <td class="text-center">{{ $row->machine_code }}</td>
                    <td class="text-center">{{ $row->heat_number }}</td>
                    <td>
                        {{ $row->item->name ?? $row->item_code }}
                        @if($row->size)
                            <span style="color:#666; font-size:8pt;">({{ $row->size }})</span>
                        @endif
                        @if($row->remark)
                            <br><span style="color:red; font-weight:bold;">{{ $row->remark }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($row->work_hours, 2) }}</td>
                    <td class="text-right">{{ $row->target_qty }}</td>
                    <td class="text-right">{{ $row->actual_qty }}</td>
                    <td class="text-center">
                        {{-- KPI per pekerjaan --}}
                        <span class="{{ $class }}">{{ $row->achievement_percent }}%</span>

                        {{-- Rata2 harian (dari daily_kpi_operator) --}}
                        @if($dailyPct !== null)
                            <div style="margin-top:3px; border-top:1px dotted #bbb; padding-top:3px;">
                                <span style="font-size:7pt; color:#999;">rata2 harian</span><br>
                                <span class="{{ $avgClass }}" style="font-size:8pt;">
                                    {{ number_format($dailyPct, 2) }}%
                                </span>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">
                        Data tidak ditemukan untuk tanggal ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Tanda Tangan -->
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
        </tr>
    </table>

    {{-- ============================================================ --}}
    {{-- RINGKASAN PERFORMA RANGE --}}
    {{-- ============================================================ --}}
    @if(isset($summaryData) && $summaryData['day_count'] > 0)
    <div style="margin-top: 28px; page-break-inside: avoid; font-size: 9pt;">
        <div style="border-top: 2px solid #555; padding-top: 10px;">
            <h3 style="margin: 0 0 10px 0; font-size: 10pt; color: #333;">
                Ringkasan Performa — {{ $date }}
            </h3>

            <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="border:1px solid #999; padding:5px 8px; text-align:center; width:40%;">Kategori</th>
                        <th style="border:1px solid #999; padding:5px 8px; text-align:center; width:20%;">Jumlah Hari</th>
                        <th style="border:1px solid #999; padding:5px 8px; text-align:center; width:20%;">Persentase</th>
                        <th style="border:1px solid #999; padding:5px 8px; text-align:center; width:20%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border:1px solid #999; padding:5px 8px;">✅ Di atas target (KPI ≥ 85%)</td>
                        <td style="border:1px solid #999; padding:5px 8px; text-align:center; font-weight:bold; color:#166534;">
                            {{ $summaryData['days_above'] }} hari
                        </td>
                        <td style="border:1px solid #999; padding:5px 8px; text-align:center; font-weight:bold; color:#166534;">
                            {{ $summaryData['pct_above'] }}%
                        </td>
                        <td style="border:1px solid #999; padding:5px 8px; text-align:center;">
                            <span style="color:#166534; font-weight:bold;">MEMENUHI</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #999; padding:5px 8px;">❌ Di bawah target (KPI &lt; 85%)</td>
                        <td style="border:1px solid #999; padding:5px 8px; text-align:center; font-weight:bold; color:#dc2626;">
                            {{ $summaryData['days_below'] }} hari
                        </td>
                        <td style="border:1px solid #999; padding:5px 8px; text-align:center; font-weight:bold; color:#dc2626;">
                            {{ $summaryData['pct_below'] }}%
                        </td>
                        <td style="border:1px solid #999; padding:5px 8px; text-align:center;">
                            <span style="color:#dc2626; font-weight:bold;">TIDAK MEMENUHI</span>
                        </td>
                    </tr>
                    <tr style="background-color:#f9f9f9;">
                        <td style="border:1px solid #999; padding:5px 8px;"><strong>Total Hari Kerja</strong></td>
                        <td style="border:1px solid #999; padding:5px 8px; text-align:center; font-weight:bold;">{{ $summaryData['day_count'] }} hari</td>
                        <td style="border:1px solid #999; padding:5px 8px; text-align:center;">—</td>
                        <td style="border:1px solid #999; padding:5px 8px; text-align:center;">—</td>
                    </tr>
                </tbody>
            </table>

            {{-- Rata-rata KPI keseluruhan --}}
            @php
                $avgColor = $summaryData['overall_avg'] >= 100 ? '#166534'
                          : ($summaryData['overall_avg'] >= 85  ? '#d97706' : '#dc2626');
                $avgLabel = $summaryData['overall_avg'] >= 100 ? 'SANGAT BAIK'
                          : ($summaryData['overall_avg'] >= 85  ? 'CUKUP'     : 'PERLU PERBAIKAN');
            @endphp
            <div style="margin-top:12px; padding:10px 14px; border:2px solid {{ $avgColor }}; border-radius:4px;">
                <span style="font-size:9pt; color:#555;">Rata-rata KPI Harian Selama Periode</span><br>
                <span style="font-size:14pt; font-weight:bold; color:{{ $avgColor }};">
                    {{ number_format($summaryData['overall_avg'], 2) }}%
                </span>
                <span style="font-size:8pt; color:{{ $avgColor }}; margin-left:6px;">— {{ $avgLabel }}</span>
            </div>
        </div>
    </div>
    @endif

    <div class="pdf-footer">
        IP: {{ request()->ip() }} &nbsp;|&nbsp;
        User: {{ auth()->user()->name ?? 'Guest' }} &nbsp;|&nbsp;
        Digenerate: {{ \Carbon\Carbon::now('Asia/Jakarta')->format('d/m/Y H:i:s') }}
    </div>

</body>

</html>