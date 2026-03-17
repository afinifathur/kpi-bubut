<!DOCTYPE html>
<html>

<head>
    <title>Laporan Harian Kerusakan</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
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
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 5px;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background-color: #f0f0f0;
            text-align: center;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* Column Widths */
        .col-mc {
            width: 10%;
            text-align: center;
        }

        .col-detail {
            width: 45%;
        }

        .col-qty {
            width: 10%;
            text-align: right;
        }

        .col-op {
            width: 25%;
        }

        .signatures {
            margin-top: 40px;
            width: 100%;
            border: none;
        }

        .signatures td {
            border: none;
            text-align: center;
            vertical-align: top;
            padding-top: 60px;
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
        <h2>Laporan Harian Kerusakan (Reject)</h2>
        <p>{{ \Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-mc">Mesin</th>
                <th class="col-detail">Detail Item & Alasan</th>
                <th class="col-qty">Qty</th>
                <th class="col-op">Operator</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($rows as $row)
                @php $total += $row->reject_qty; @endphp
                <tr>
                    <td class="text-center">{{ $row->machine_code }}</td>
                    <td>
                        <strong>{{ $row->item->name ?? $row->item_code }}</strong><br>
                        <span style="color: #c2410c; font-size: 9pt;">Reason: {{ $row->reject_reason }}</span>
                        @if($row->note)
                            <br><small style="color: #64748b; font-style: italic;">Note: {{ $row->note }}</small>
                        @endif
                    </td>
                    <td class="text-right"><strong>{{ number_format($row->reject_qty) }}</strong> pcs</td>
                    <td>
                        {{ $row->operator->name ?? $row->operator_code }}<br>
                        <small>{{ $row->operator_code }}</small>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f8fafc;">
                <th colspan="2" class="text-right">TOTAL</th>
                <th class="text-right">{{ number_format($total) }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <table class="signatures">
        <tr>
            <td width="33%">
                Dibuat Oleh,<br><br><br>
                ( ....................... )<br>Admin
            </td>
            <td width="33%">
                Diperiksa Oleh,<br><br><br>
                ( ....................... )<br>QC Inspector
            </td>
            <td width="33%">
                Diketahui Oleh,<br><br><br>
                ( ....................... )<br>Manager
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
