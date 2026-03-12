<!DOCTYPE html>
<html>

<head>
    <title>Laporan Harian Mesin</title>
    <style>
        @page {
            margin: 1cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #edeff2;
            padding-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            color: #1a202e;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 14pt;
        }

        .header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 9pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #e2e8f0;
            padding: 8px 6px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        th {
            background-color: #f8fafc;
            text-align: left;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            font-size: 7pt;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .type-badge {
            font-weight: bold;
            font-size: 7pt;
            padding: 2px 4px;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .type-check { color: #059669; }
        .type-downtime { color: #dc2626; }

        .check-item {
            display: inline-block;
            margin-right: 4px;
            font-size: 7pt;
        }
        .check-yes { color: #059669; }
        .check-no { color: #dc2626; }

        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
            font-size: 7pt;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            padding-top: 5px;
        }

        .note {
            font-style: italic;
            color: #64748b;
            font-size: 7pt;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Laporan Harian Mesin</h2>
        <p>{{ \Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%">Mesin</th>
                <th style="width: 10%">Tipe</th>
                <th style="width: 58%">Detail Laporan (Checklist / Alasan)</th>
                <th style="width: 20%">Spek / Durasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="font-bold">
                        {{ $row->machine->name ?? $row->machine_code }}
                        <div style="font-size: 6pt; color: #94a3b8; font-weight: normal;">{{ $row->machine_code }}</div>
                    </td>
                    <td class="text-center font-bold">
                        @if($row->entry_type === 'check')
                            <span class="type-check">CEK</span>
                        @else
                            <span class="type-downtime">STOP</span>
                        @endif
                    </td>
                    <td>
                        @if($row->entry_type === 'check')
                            <div style="margin-bottom: 4px;">
                                <span class="check-item {{ $row->check_cekam === 'Ya' ? 'check-yes' : 'check-no' }}">CEKAM: {{ $row->check_cekam }}</span>
                                <span class="check-item {{ $row->check_air_ozo === 'Ya' ? 'check-yes' : 'check-no' }}">OZON: {{ $row->check_air_ozo }}</span>
                                <span class="check-item {{ $row->check_eretan === 'Ya' ? 'check-yes' : 'check-no' }}">ERETAN: {{ $row->check_eretan }}</span>
                                <span class="check-item {{ $row->check_pisau === 'Ya' ? 'check-yes' : 'check-no' }}">PISAU: {{ $row->check_pisau }}</span>
                                <span class="check-item {{ $row->check_kebersihan === 'Ya' ? 'check-yes' : 'check-no' }}">BERSIH: {{ $row->check_kebersihan }}</span>
                                <span class="check-item {{ $row->check_oli === 'Ya' ? 'check-yes' : 'check-no' }}">OLI: {{ $row->check_oli }}</span>
                            </div>
                            <div class="font-bold" style="font-size: 7pt;">
                                {{ $row->size_category }} | Mode: {{ ucfirst($row->rpm_feeding_mode) }}
                            </div>
                        @else
                            <div class="font-bold" style="color: #dc2626;">{{ $row->reason }}</div>
                            <div style="font-size: 7pt; color: #64748b;">
                                Jam: {{ \Carbon\Carbon::parse($row->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($row->end_time)->format('H:i') }}
                            </div>
                        @endif
                        
                        @if($row->note)
                            <div class="note">Catatan: {{ $row->note }}</div>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($row->entry_type === 'check')
                            <div>RPM: <span class="font-bold">{{ $row->rpm_value }}</span></div>
                            <div>Feed: <span class="font-bold">{{ $row->feeding_value }}</span></div>
                        @else
                            <div class="font-bold" style="color: #dc2626; font-size: 10pt;">{{ $row->duration_minutes }} <span style="font-size: 7pt;">min</span></div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Downtime Report - {{ config('app.name') }} &nbsp;|&nbsp;
        Digenerate: {{ \Carbon\Carbon::now('Asia/Jakarta')->format('d/m/Y H:i:s') }} &nbsp;|&nbsp;
        User: {{ auth()->user()->name ?? 'Guest' }}
    </div>
</body>

</html>
