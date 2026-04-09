<!DOCTYPE html>
<html>

<head>
    <title>Laporan Downtime</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
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
        <h2>Laporan Harian Mesin (Downtime & Cek Harian)</h2>
        <p>Range: {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 15%">Tanggal / Jam</th>
                <th style="width: 15%">Mesin</th>
                <th style="width: 10%">Tipe</th>
                <th style="width: 45%">Detail / Masalah / Spek</th>
                <th style="width: 10%">Durasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-xs">
                        {{ \Carbon\Carbon::parse($row->downtime_date)->format('d/m/y') }}
                        @if($row->start_time)
                            <br><small>{{ \Carbon\Carbon::parse($row->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($row->end_time)->format('H:i') }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $row->machine->name ?? $row->machine_code }}
                        <br><small>({{ $row->machine_code }})</small>
                    </td>
                    <td class="text-center">
                        {{ $row->entry_type === 'check' ? 'CHECK' : 'DOWNTIME' }}
                    </td>
                    <td>
                        @if($row->entry_type === 'check')
                            <strong>{{ $row->size_category }} - {{ strtoupper($row->rpm_feeding_mode) }}</strong><br>
                            RPM (S/I): {{ $row->rpm_value }} / {{ $row->rpm_id_value ?? '-' }}<br>
                            Feed (S/I): {{ $row->feeding_value }} / {{ $row->feeding_id_value ?? '-' }}<br>
                            Cek: {{ $row->check_cekam }}/{{ $row->check_air_ozo }}/{{ $row->check_eretan }}/{{ $row->check_pisau }}/{{ $row->check_kebersihan }}/{{ $row->check_oli }}
                        @else
                            <strong>{{ $row->reason }}</strong><br>
                            {{ $row->note ?? '-' }}
                        @endif
                    </td>
                    <td class="text-right">
                        {{ $row->entry_type === 'downtime' ? $row->duration_minutes . 'm' : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">
                        Tidak ada data untuk periode ini.
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

    <div class="pdf-footer">
        IP: {{ request()->ip() }} &nbsp;|&nbsp;
        User: {{ auth()->user()->name ?? 'Guest' }} &nbsp;|&nbsp;
        Digenerate: {{ \Carbon\Carbon::now('Asia/Jakarta')->format('d/m/Y H:i:s') }}
    </div>

</body>

</html>
