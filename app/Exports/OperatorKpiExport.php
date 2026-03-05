<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OperatorKpiExport implements FromCollection, WithHeadings, WithStyles
{
    protected string $startDate;
    protected string $endDate;
    protected ?string $operatorCode;

    public function __construct(string $startDate, string $endDate, ?string $operatorCode = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->operatorCode = $operatorCode;
    }

    public function collection()
    {
        $query = \App\Models\ProductionLog::with(['machine', 'item'])
            ->whereBetween('production_date', [$this->startDate, $this->endDate]);

        if ($this->operatorCode && $this->operatorCode !== 'all') {
            $query->where('operator_code', $this->operatorCode);
        }

        return $query
            ->orderBy('production_date')
            ->orderBy('shift')
            ->orderBy('operator_code')
            ->orderBy('time_start')
            ->get()
            ->map(function ($row) {
                // Determine operator name
                $opName = \App\Models\MdOperatorMirror::where('code', $row->operator_code)->value('name') ?? $row->operator_code;

                // Determine Item Name + Size
                $itemName = $row->item->name ?? $row->item_code;
                if (!empty($row->size)) {
                    $itemName .= ' (' . $row->size . ')';
                }

                return [
                    'tanggal' => $row->production_date,
                    'shift' => $row->shift,
                    'operator' => $opName,
                    'machine' => $row->machine_code,
                    'item' => $itemName,
                    'time_start' => $row->time_start,
                    'time_end' => $row->time_end,
                    'target' => $row->target_qty,
                    'actual' => $row->actual_qty,
                    'kpi' => $row->achievement_percent . '%',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'SF',
            'Operator',
            'Mesin',
            'Item & Size',
            'Waktu Mulai',
            'Waktu Selesai',
            'Target',
            'Aktual',
            'KPI (%)',
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

