<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class OeeExport implements FromView, ShouldAutoSize, WithColumnFormatting
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('exports.oee_excel', $this->data);
    }

    /**
     * Define explicit Excel column formats for raw variables
     */
    public function columnFormats(): array
    {
        return [
            'B' => '#,##0.00', // Planned Capacity (Jam)
            'C' => '#,##0.00', // Work Hours (Jam)
            'D' => '#,##0.00', // Downtime (Jam)
            'E' => '#,##0',    // Target Qty
            'F' => '#,##0',    // Actual Qty
            'G' => '#,##0',    // Reject Qty
            'H' => '0.00%',    // Availability (%)
            'I' => '0.00%',    // Performance (%)
            'J' => '0.00%',    // Quality (%)
            'K' => '0.00%',    // OEE (%)
        ];
    }
}
