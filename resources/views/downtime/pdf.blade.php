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
        <h2>Laporan Downtime Harian</h2>
        <p>Tanggal: {{ \Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 15%">Mesin</th>
                <th style="width: 20%">Operator</th>
                <th style="width: 10%">Durasi</th>
                <th style="width: 50%">Catatan Masalah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($list as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $row->machine->name ?? $row->machine_code }}
                    </td>
                    <td>
                        {{ $row->operator->name ?? $row->operator_code }}
                    </td>
                    <td class="text-right" style="font-weight: bold;">
                        {{ $row->duration_minutes }} Min
                    </td>
                    <td>
                        {{ $row->note }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">
                        Tidak ada data downtime untuk tanggal ini.
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
        Digenerate: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
    </div>

</body>

</html>