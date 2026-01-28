<?php

namespace App\Exports;

use App\Models\DailyKpiOperator;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OperatorKpiExport implements FromCollection, WithHeadings
{
    protected string $date;

    public function __construct(string $date)
    {
        $this->date = $date;
    }

    public function collection()
    {
        return \App\Models\ProductionLog::with(['machine', 'item'])
            ->where('production_date', $this->date)
            ->orderBy('shift')
            ->orderBy('operator_code')
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
                    'shift' => $row->shift,
                    'operator' => $opName,
                    'machine' => $row->machine_code, // Keep code as requested for machine
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
            'SF',
            'Operator',
            'Mesin',
            'Item & Size',
            'Waktu Mulai',
            'Waktu Selesai',
            'Target',
            'Aktual',
            'KPI',
        ];
    }
}

