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
            margin-top: 40px;
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            text-align: center;
            width: 50%;
            vertical-align: bottom;
            padding: 10px;
        }
        .signature-box {
            height: 80px;
            margin-bottom: 5px;
            position: relative;
        }
        .signature-img {
            max-height: 80px;
            max-width: 180px;
        }
        .signature-placeholder {
            padding-top: 40px;
            color: #94a3b8;
            font-style: italic;
            font-size: 8pt;
        }
        .signature-info {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
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
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80pt;
            color: rgba(220, 38, 38, 0.1);
            z-index: -1000;
            font-weight: bold;
            text-transform: uppercase;
        }
        .approval-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-pending { background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
        .badge-approved { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-rejected { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    @if($isDraft)
        <div class="watermark">DRAFT</div>
    @endif
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
            <td class="label">Nama Operator</td>
            <td>: {{ $report->operator_name ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Traceable Link</td>
            <td colspan="3">: {{ $report->data_link ?: '-' }}</td>
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

    <table class="metadata-table" style="margin-top: 15px;">
        <tr>
            <td class="label">Target Penyelesaian</td>
            <td>: {{ $report->target_completion_date ? $report->target_completion_date->isoFormat('D MMMM YYYY') : '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Hasil Monitoring</div>
    <div class="content-box">
        {!! $report->monitoring_result ? nl2br(e($report->monitoring_result)) : '<i style="color:#94a3b8">Belum ada hasil monitoring.</i>' !!}
    </div>

    <div class="section-title">Lampiran Bukti</div>
    <div class="content-box" style="padding: 10px;">
        @php
            $images = [];
            $othersCount = 0;
            if ($report->evidence_files) {
                foreach ($report->evidence_files as $file) {
                    $ext = pathinfo($file['path'], PATHINFO_EXTENSION);
                    if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
                        $images[] = $file;
                    } else {
                        $othersCount++;
                    }
                }
            }
            $displayImages = array_slice($images, 0, 3);
            $extraImagesCount = count($images) - count($displayImages);
        @endphp

        @if(count($displayImages) > 0)
            <div style="width: 100%;">
                @foreach($displayImages as $img)
                    <div style="display: inline-block; width: 30%; margin-right: 2%; margin-bottom: 10px; vertical-align: top;">
                        <img src="{{ public_path('storage/' . $img['path']) }}" style="width: 100%; height: 120px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                @endforeach
            </div>
            @if($extraImagesCount > 0 || $othersCount > 0)
                <div style="font-size: 8pt; color: #64748b; margin-top: 5px;">
                    + {{ $extraImagesCount + $othersCount }} lampiran lainnya (lihat di sistem)
                </div>
            @endif
        @else
            <i style="color:#94a3b8">Tidak ada lampiran bukti visual.</i>
        @endif
    </div>

    @if($report->additional_notes)
        <div class="section-title">Catatan Tambahan</div>
        <div class="content-box">
            {!! nl2br(e($report->additional_notes)) !!}
        </div>
    @endif

    <div class="section-title">Informasi Approval</div>
    <table class="metadata-table">
        <tr>
            <td class="label">Status Approval</td>
            <td>
                <span class="approval-badge badge-{{ $report->approval_status }}">
                    {{ strtoupper($report->approval_status) }}
                </span>
            </td>
            <td class="label">Disetujui Oleh</td>
            <td>: {{ $report->approver->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Approval</td>
            <td>: {{ $report->approved_at ? $report->approved_at->isoFormat('D MMM YYYY, HH:mm') : '-' }}</td>
            <td class="label">Catatan Approval</td>
            <td>: {{ $report->approval_note ?: '-' }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div style="margin-bottom: 5px; font-weight: bold; color: #334155;">Admin HR (Submitter)</div>
                <div class="signature-box">
                    @if($report->submitter && $report->submitter->signature_path && file_exists(public_path('storage/' . $report->submitter->signature_path)))
                        <img src="{{ public_path('storage/' . $report->submitter->signature_path) }}" class="signature-img">
                    @else
                        <div class="signature-placeholder">(Signature not available)</div>
                    @endif
                </div>
                <div style="border-top: 1px solid #334155; padding-top: 5px; margin: 0 40px;">
                    <strong>{{ $report->submitter->name ?? ($report->creator->name ?? '.................') }}</strong>
                </div>
                <div class="signature-info">
                    Submitted: {{ $report->submitted_at ? $report->submitted_at->isoFormat('D MMM YYYY, HH:mm') : '-' }}
                </div>
            </td>
            <td>
                <div style="margin-bottom: 5px; font-weight: bold; color: #334155;">Manager HR (Approver)</div>
                <div class="signature-box">
                    @if($report->approval_status === 'approved')
                        @if($report->approver && $report->approver->signature_path && file_exists(public_path('storage/' . $report->approver->signature_path)))
                            <img src="{{ public_path('storage/' . $report->approver->signature_path) }}" class="signature-img">
                        @else
                            <div class="signature-placeholder">(Signature not available)</div>
                        @endif
                    @else
                        <div class="signature-placeholder" style="color: #ef4444; font-weight: bold;">WAITING APPROVAL</div>
                    @endif
                </div>
                <div style="border-top: 1px solid #334155; padding-top: 5px; margin: 0 40px;">
                    <strong>{{ $report->approver->name ?? '.................' }}</strong>
                </div>
                <div class="signature-info">
                    Approved: {{ $report->approved_at ? $report->approved_at->isoFormat('D MMM YYYY, HH:mm') : '-' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak pada: {{ \Carbon\Carbon::now('Asia/Jakarta')->isoFormat('D MMM YYYY, HH:mm') }} | KPI-Bubut Tracking System
    </div>
</body>
</html>
