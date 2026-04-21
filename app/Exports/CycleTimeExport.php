<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CycleTimeExport implements FromCollection, WithHeadings, WithStyles
{
    protected string $startDate;
    protected string $endDate;
    protected string $itemCode;

    public function __construct(string $startDate, string $endDate, string $itemCode)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->itemCode = $itemCode;
    }

    public function collection()
    {
        $query = \App\Models\ProductionLog::with(['machine', 'item', 'operator'])
            ->whereBetween('production_date', [$this->startDate, $this->endDate])
            ->where('item_code', $this->itemCode);

        // Calculate global average for the item
        $averageSec = clone $query;
        $averageCycleTimeSec = $averageSec->avg('cycle_time_used_sec') ?? 0;

        return $query
            ->orderBy('production_date')
            ->orderBy('shift')
            ->get()
            ->map(function ($row) use ($averageCycleTimeSec) {
                // Determine operator name
                $opName = $row->operator->name ?? $row->operator_code;
                $machineName = $row->machine->name ?? $row->machine_code;

                // Cycle time display
                $rowMins = floor($row->cycle_time_used_sec / 60);
                $rowSecs = $row->cycle_time_used_sec % 60;
                $cycleTimeDisplay = "{$rowMins}m {$rowSecs}s";

                // Anomaly check
                $diffPercent = 0;
                $status = 'Normal';
                if ($averageCycleTimeSec > 0) {
                    $diffPercent = (($row->cycle_time_used_sec - $averageCycleTimeSec) / $averageCycleTimeSec) * 100;
                    if ($diffPercent > 20) {
                        $status = '+' . number_format($diffPercent, 1) . '% Tinggi';
                    } elseif ($diffPercent < -20) {
                        $status = number_format($diffPercent, 1) . '% Rendah';
                    }
                }

                if (!empty($row->remark)) {
                    $status .= ' | Ket: ' . $row->remark;
                }

                return [
                    'tanggal' => \Carbon\Carbon::parse($row->production_date)->format('d/m/Y'),
                    'shift' => 'Shift ' . $row->shift,
                    'operator' => $opName . ' (' . $row->operator_code . ')',
                    'machine' => $machineName,
                    'actual' => $row->actual_qty,
                    'jam_kerja' => $row->work_hours,
                    'cycle_time' => $cycleTimeDisplay,
                    'status' => $status,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Tanggal Input',
            'Shift',
            'Operator',
            'Mesin',
            'Hasil (PCS)',
            'Jam Kerja',
            'Cycle Time',
            'Status V.S Rata2',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Bold the header row
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
