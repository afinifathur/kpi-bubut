<?php

namespace App\Exports;

use App\Models\ProductionLog;
use App\Models\MdMachineMirror;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MachineKpiExport implements FromCollection, WithHeadings, WithStyles, WithMapping
{
    protected string $startDate;
    protected string $endDate;
    protected ?string $machineCode;

    public function __construct(string $startDate, string $endDate, ?string $machineCode = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->machineCode = $machineCode;
    }

    public function collection()
    {
        $query = ProductionLog::with(['operator', 'item'])
            ->whereBetween('production_date', [$this->startDate, $this->endDate]);

        if ($this->machineCode && $this->machineCode !== 'all') {
            $query->where('machine_code', $this->machineCode);
        }

        return $query
            ->orderBy('production_date')
            ->orderBy('machine_code')
            ->orderBy('shift')
            ->orderBy('time_start')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'SF',
            'Mesin',
            'Operator',
            'Item & Size',
            'Jam Jln',
            'Target',
            'Aktual',
            'KPI (%)',
        ];
    }

    public function map($row): array
    {
        // Item Name + Size
        $itemName = $row->item->name ?? $row->item_code;
        if (!empty($row->size)) {
            $itemName .= ' (' . $row->size . ')';
        }

        return [
            $row->production_date,
            $row->shift,
            $row->machine_code,
            $row->operator->name ?? $row->operator_code,
            $itemName,
            number_format($row->work_hours, 2),
            $row->target_qty,
            $row->actual_qty,
            $row->achievement_percent . '%',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

