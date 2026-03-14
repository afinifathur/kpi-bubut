<!DOCTYPE html>
<html>
<head>
    <title>Laporan HR - {{ $report->report_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.5;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .header-title {
            font-size: 18pt;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
        }
        .report-number {
            font-size: 11pt;
            color: #666;
            font-family: monospace;
        }
        .section-title {
            background-color: #f8fafc;
            border-left: 4px solid #2563eb;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 11pt;
            margin-top: 20px;
            margin-bottom: 10px;
            text-transform: uppercase;
            color: #334155;
        }
        .metadata-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .metadata-table td {
            padding: 5px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            width: 150px;
            color: #64748b;
        }
        .content-box {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            background-color: #ffffff;
            min-height: 50px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9pt;
            color: white;
            text-transform: uppercase;
        }
        .status-Open { background-color: #ef4444; }
        .status-Investigating { background-color: #f59e0b; }
        .status-Action-Plan { background-color: #3b82f6; }
        .status-Monitoring { background-color: #8b5cf6; }
        .status-Closed { background-color: #10b981; }

        .signatures {
            margin-top: 50px;
            width: 100%;
        }
        .signatures td {
            text-align: center;
            width: 50%;
            padding-top: 60px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="header-title">Laporan HR (Issue Tracking)</div>
                    <div class="report-number">{{ $report->report_number }}</div>
                </td>
                <td style="text-align: right;">
                    <div class="status-badge status-{{ str_replace(' ', '-', $report->status) }}">
                        {{ $report->status }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Informasi Dasar</div>
    <table class="metadata-table">
        <tr>
            <td class="label">Kategori Issue</td>
            <td>: {{ $report->category }}</td>
            <td class="label">Tanggal Laporan</td>
            <td>: {{ \Carbon\Carbon::parse($report->report_date)->isoFormat('D MMMM YYYY') }}</td>
        </tr>
        <tr>
            <td class="label">Dilaporkan Oleh</td>
            <td>: {{ $report->creator->name ?? 'System' }}</td>
            <td class="label">Traceable Link</td>
            <td>: {{ $report->data_link ?: '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Judul Laporan</div>
    <div style="font-size: 12pt; font-weight: bold; margin-bottom: 10px;">
        {{ $report->title }}
    </div>

    <div class="section-title">Deskripsi Masalah</div>
    <div class="content-box">
        {!! nl2br(e($report->description)) !!}
    </div>

    <div class="section-title">Penyebab Masalah (Root Cause)</div>
    <div class="content-box">
        {!! $report->root_cause ? nl2br(e($report->root_cause)) : '<i style="color:#94a3b8">Belum ada analisis penyebab.</i>' !!}
    </div>

    <div class="section-title">Tindakan Perbaikan (Corrective Action)</div>
    <div class="content-box">
        {!! $report->corrective_action ? nl2br(e($report->corrective_action)) : '<i style="color:#94a3b8">Belum ada tindakan perbaikan.</i>' !!}
    </div>

    <table class="signatures">
        <tr>
            <td>
                Dibuat Oleh,<br><br><br>
                ( ....................... )<br>
                Admin HR
            </td>
            <td>
                Diperiksa Oleh,<br><br><br>
                ( ....................... )<br>
                Manager
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak pada: {{ \Carbon\Carbon::now('Asia/Jakarta')->isoFormat('D MMM YYYY, HH:mm') }} | KPI-Bubut Tracking System
    </div>
</body>
</html>
