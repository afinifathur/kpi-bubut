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
            'B' => '#,##0.00', // Total Runtime Mesin (Jam)
            'C' => '#,##0.00', // Downtime (Jam)
            'D' => '#,##0',    // Target Qty
            'E' => '#,##0',    // Actual Qty
            'F' => '#,##0',    // Reject Qty
            'G' => '0.00%',    // Availability (%)
            'H' => '0.00%',    // Performance (%)
            'I' => '0.00%',    // Quality (%)
            'J' => '0.00%',    // OEE (%)
        ];
    }
}
