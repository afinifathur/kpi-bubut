<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        @page {
            margin: 15px 15px 35px 15px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            font-size: 7.5pt;
            line-height: 1.25;
        }
        .report-header {
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 2px solid #334155;
            padding-bottom: 5px;
            position: relative;
        }
        .company-name {
            font-size: 9pt;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 13pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 2px 0;
        }
        .period-info {
            font-size: 8pt;
            color: #475569;
            font-weight: 500;
        }
        .meta-info {
            position: absolute;
            right: 0;
            top: 0;
            text-align: right;
            font-size: 7pt;
            color: #64748b;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            margin-top: 10px;
        }
        thead {
            display: table-header-group;
        }
        tr {
            page-break-inside: avoid;
        }
        .data-table th {
            background-color: #e2e8f0;
            color: #1e293b;
            border: 1px solid #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7pt;
            padding: 5px 2px;
            text-align: center;
        }
        .data-table td {
            border: 1px solid #94a3b8;
            padding: 4px 2px;
            vertical-align: middle;
            text-align: center;
        }
        .row-even {
            background-color: #ffffff;
        }
        .row-odd {
            background-color: #f8fafc;
        }
        .col-rank {
            font-weight: bold;
            font-size: 7.5pt;
        }
        .col-operator {
            text-align: left !important;
            font-weight: bold;
            font-size: 8pt;
            padding-left: 5px !important;
            white-space: nowrap;
            overflow: hidden;
        }
        .col-kpi {
            font-weight: bold;
            background-color: #f1f5f9;
            font-size: 7.5pt;
        }
        .col-days {
            background-color: #f1f5f9;
            font-size: 7.5pt;
        }
        .matrix-cell {
            text-align: center;
            font-size: 6.5pt;
            padding: 4px 1px !important;
        }
        
        /* Light conditional formatting style (text color + very light background tint) */
        .kpi-empty {
            color: #94a3b8;
            background-color: #f8fafc;
        }
        .kpi-excellent {
            color: #047857;
            background-color: #dcfce7;
            font-weight: bold;
        }
        .kpi-good {
            color: #065f46;
            background-color: #f0fdf4;
        }
        .kpi-average {
            color: #b45309;
            background-color: #fef3c7;
        }
        .kpi-poor {
            color: #c2410c;
            background-color: #fff7ed;
        }
        .kpi-critical {
            color: #b91c1c;
            background-color: #fee2e2;
            font-weight: bold;
        }
        
        .legend-container {
            margin-top: 15px;
            font-size: 7pt;
            color: #475569;
            page-break-inside: avoid;
        }
        .legend-item {
            display: inline-block;
            margin-right: 10px;
            padding: 1px 4px;
            border-radius: 2px;
            font-weight: 600;
            border: 1px solid #cbd5e1;
        }
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
            width: 100%;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            border: none;
            text-align: center;
            width: 33.33%;
            padding: 5px;
        }
        .signature-line {
            margin-top: 45px;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 20px;
            font-size: 6.5pt;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
        }
        .footer-left {
            float: left;
        }
    </style>
</head>
<body>

    @php
        if (!function_exists('getKpiStyleClass')) {
            function getKpiStyleClass($kpi) {
                if ($kpi === null) {
                    return 'kpi-empty';
                }
                if ($kpi >= 95) {
                    return 'kpi-excellent';
                }
                if ($kpi >= 90) {
                    return 'kpi-good';
                }
                if ($kpi >= 80) {
                    return 'kpi-average';
                }
                if ($kpi >= 70) {
                    return 'kpi-poor';
                }
                return 'kpi-critical';
            }
        }

        $datesCount = count($dates);
        // Distribute remaining 70% of table width dynamically to the daily columns (reduces Operator column to 18%)
        $dateColWidth = $datesCount > 0 ? (70 / $datesCount) : 2.25;
    @endphp

    {{-- Report Header --}}
    <div class="report-header">
        <div class="company-name">PT. PERONI KARYA SENTRA</div>
        <div class="report-title">{{ $reportTitle }}</div>
        <div class="period-info">
            Period: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}
            @if($departmentName)
                | Department: {{ $departmentName }}
            @endif
        </div>
        <div class="meta-info">
            Generated By: {{ $generated_by }}<br>
            Generated Date: {{ $generated_at }}
        </div>
    </div>

    {{-- Data Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-rank" style="width: 3%;">Rank</th>
                <th class="col-operator" style="text-align: left; width: 18%; padding-left: 5px;">Operator Name</th>
                <th class="col-kpi" style="width: 5%;">Avg KPI</th>
                <th class="col-days" style="width: 4%;">Days</th>
                @foreach($dates as $date)
                    <th class="matrix-cell" style="width: {{ $dateColWidth }}%;">{{ \Carbon\Carbon::parse($date)->format('d') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($leaderboardData as $index => $row)
                <tr class="{{ $index % 2 === 0 ? 'row-even' : 'row-odd' }}">
                    <td class="col-rank">{{ $index + 1 }}</td>
                    <td class="col-operator">{{ \Illuminate\Support\Str::limit($row['operator_name'], 25) }}</td>
                    <td class="col-kpi">{{ number_format($row['average_kpi'], 1) }}%</td>
                    <td class="col-days">{{ $row['working_days'] }}</td>
                    @foreach($dates as $date)
                        @php
                            $val = $row['matrix'][$date];
                        @endphp
                        <td class="matrix-cell {{ getKpiStyleClass($val) }}">
                            {{ $val !== null ? round($val) : '-' }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Legend --}}
    <div class="legend-container">
        <span style="font-weight: bold; margin-right: 5px;">Legend KPI:</span>
        <span class="legend-item kpi-excellent">&ge;95%</span>
        <span class="legend-item kpi-good">90-94%</span>
        <span class="legend-item kpi-average">80-89%</span>
        <span class="legend-item kpi-poor">70-79%</span>
        <span class="legend-item kpi-critical">&lt;70%</span>
        <span class="legend-item kpi-empty">No activity (-)</span>
    </div>

    {{-- Signatures --}}
    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    <p>Prepared By,</p>
                    <p class="signature-line">_____________________</p>
                    <p style="color: #64748b; font-size: 7.5pt; margin-top: 2px;">HRD</p>
                </td>
                <td>
                    <p>Reviewed By,</p>
                    <p class="signature-line">_____________________</p>
                    <p style="color: #64748b; font-size: 7.5pt; margin-top: 2px;">Manager</p>
                </td>
                <td>
                    <p>Approved By,</p>
                    <p class="signature-line">_____________________</p>
                    <p style="color: #64748b; font-size: 7.5pt; margin-top: 2px;">Director</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <span class="footer-left">PT. PERONI KARYA SENTRA &nbsp;|&nbsp; Generated By: {{ $generated_by }} &nbsp;|&nbsp; Generated Date: {{ $generated_at }}</span>
    </div>

    {{-- DomPDF Script for Page Numbers --}}
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 7;
            $font = $fontMetrics->get_font("Helvetica, Arial, sans-serif", "normal");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $pdf->page_text($pdf->get_width() - $width - 15, $pdf->get_height() - 20, $text, $font, $size, array(0.4, 0.4, 0.4));
        }
    </script>

</body>
</html>
