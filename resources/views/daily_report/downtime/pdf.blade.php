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

    @php
        $standards = [
            '1/2" - 3/4"' => [
                'kasar' => ['rpm' => 380, 'feeding' => 0.17],
                'finish' => ['rpm' => 450, 'feeding' => 0.2]
            ],
            '1"' => [
                'kasar' => ['rpm' => 350, 'feeding' => 0.17],
                'finish' => ['rpm' => 450, 'feeding' => 0.2]
            ],
            '1-1/4" - 2"' => [
                'kasar' => ['rpm' => 300, 'feeding' => 0.17],
                'finish' => ['rpm' => 380, 'feeding' => 0.2]
            ],
            '2" - 2-1/2"' => [
                'kasar' => ['rpm' => 300, 'feeding' => 0.17],
                'finish' => ['rpm' => 350, 'feeding' => 0.2]
            ],
            '3" - 4"' => [
                'kasar' => ['rpm' => 250, 'feeding' => 0.17],
                'finish' => ['rpm' => 320, 'feeding' => 0.2]
            ],
            '5" - 6"' => [
                'kasar' => ['rpm' => 220, 'feeding' => 0.17],
                'finish' => ['rpm' => 260, 'feeding' => 0.2]
            ],
            '8"' => [
                'kasar' => ['rpm' => 200, 'feeding' => 0.16],
                'finish' => ['rpm' => 240, 'feeding' => 0.2]
            ]
        ];

        if (!function_exists('getPdfColor')) {
            function getPdfColor($input, $std) {
                if (!$input || !$std) return '#64748b'; // slate-500
                $val = (float) $input;
                $diff = abs($val - $std) / $std;
                return $diff > 0.2 ? '#dc2626' : '#059669'; // red-600 vs emerald-600
            }
        }
    @endphp

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
            @foreach($rows->groupBy('machine_code') as $machineCode => $mRows)
                @php
                    $checks = $mRows->where('entry_type', 'check');
                    $stops = $mRows->where('entry_type', 'downtime');
                    $first = $mRows->first();
                    $machineName = $first->machine->name ?? $machineCode;
                @endphp
                <tr>
                    <td class="font-bold">
                        {{ $machineName }}
                        <div style="font-size: 6pt; color: #94a3b8; font-weight: normal;">{{ $machineCode }}</div>
                    </td>
                    <td class="text-center font-bold">
                        @if($checks->isNotEmpty())
                            <div class="type-check">CEK</div>
                        @endif
                        @if($stops->isNotEmpty())
                            <div class="type-downtime">STOP</div>
                        @endif
                    </td>
                    <td>
                        @if($checks->isNotEmpty())
                            @php $row = $checks->first(); @endphp
                            <div style="margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px dashed #f1f5f9;">
                                <div style="margin-bottom: 2px;">
                                    <span class="check-item {{ $row->check_cekam === 'Ya' ? 'check-yes' : 'check-no' }}">CEKAM: {{ $row->check_cekam }}</span>
                                    <span class="check-item {{ $row->check_air_ozo === 'Ya' ? 'check-yes' : 'check-no' }}">OZON: {{ $row->check_air_ozo }}</span>
                                    <span class="check-item {{ $row->check_eretan === 'Ya' ? 'check-yes' : 'check-no' }}">ERETAN: {{ $row->check_eretan }}</span>
                                    <span class="check-item {{ $row->check_pisau === 'Ya' ? 'check-yes' : 'check-no' }}">PISAU: {{ $row->check_pisau }}</span>
                                    <span class="check-item {{ $row->check_kebersihan === 'Ya' ? 'check-yes' : 'check-no' }}">BERSIH: {{ $row->check_kebersihan }}</span>
                                    <span class="check-item {{ $row->check_oli === 'Ya' ? 'check-yes' : 'check-no' }}">OLI: {{ $row->check_oli }}</span>
                                </div>
                                <div class="font-bold" style="font-size: 7pt;">
                                    Size: {{ $row->size_category }}
                                </div>
                            </div>
                        @endif

                        @if($stops->isNotEmpty())
                            @foreach($stops as $row)
                                <div style="margin-bottom: 4px;">
                                    <div class="font-bold" style="color: #dc2626;">STOP: {{ $row->reason }}</div>
                                    <div style="font-size: 7pt; color: #64748b;">
                                        Jam: {{ \Carbon\Carbon::parse($row->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($row->end_time)->format('H:i') }}
                                        @if($row->note) | Catatan: {{ $row->note }} @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                        
                        {{-- Only show notes if they weren't shown in the stop section and they are from a check entry --}}
                        @if($checks->isNotEmpty())
                            @foreach($checks as $chk)
                                @if($chk->note)
                                    <div class="note">Catatan ({{ ucfirst($chk->rpm_feeding_mode) }}): {{ $chk->note }}</div>
                                @endif
                            @endforeach
                        @endif
                    </td>
                    <td class="text-right">
                        @if($checks->isNotEmpty())
                            @foreach($checks as $row)
                                @php
                                    $std = $standards[$row->size_category][$row->rpm_feeding_mode] ?? null;
                                    $rpmColor = getPdfColor($row->rpm_value, $std['rpm'] ?? null);
                                    $rpmIdColor = getPdfColor($row->rpm_id_value, $std['rpm'] ?? null);
                                    $feedColor = getPdfColor($row->feeding_value, $std['feeding'] ?? null);
                                    $feedIdColor = getPdfColor($row->feeding_id_value, $std['feeding'] ?? null);
                                @endphp
                                <div style="margin-bottom: 8px; border-bottom: 1px dotted #f1f5f9; padding-bottom: 4px;">
                                    <div style="font-size: 6pt; font-weight: bold; color: #64748b; text-transform: uppercase;">{{ $row->rpm_feeding_mode }}</div>
                                    <div style="font-size: 7pt;">
                                        <span style="color: #64748b;">RPM SP:</span> 
                                        <span class="font-bold" style="color: {{ $rpmColor }};">{{ $row->rpm_value }}</span> 
                                        <span style="color: #64748b; margin-left: 4px;">ID:</span> 
                                        <span class="font-bold" style="color: {{ $rpmIdColor }};">{{ $row->rpm_id_value ?? '-' }}</span>
                                    </div>
                                    <div style="font-size: 7pt; margin-top: 2px;">
                                        <span style="color: #64748b;">FD SP:</span> 
                                        <span class="font-bold" style="color: {{ $feedColor }};">{{ $row->feeding_value }}</span> 
                                        <span style="color: #64748b; margin-left: 4px;">ID:</span> 
                                        <span class="font-bold" style="color: {{ $feedIdColor }};">{{ $row->feeding_id_value ?? '-' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        @if($stops->isNotEmpty())
                            @foreach($stops as $row)
                                <div class="font-bold" style="color: #dc2626; font-size: 10pt; margin-top: 4px;">{{ $row->duration_minutes }} <span style="font-size: 7pt;">min</span></div>
                            @endforeach
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
