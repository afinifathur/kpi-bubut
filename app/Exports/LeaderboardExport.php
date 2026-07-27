<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class LeaderboardExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected array $dates;
    protected array $leaderboardData;

    public function __construct(array $dates, array $leaderboardData)
    {
        $this->dates = $dates;
        $this->leaderboardData = $leaderboardData;
    }

    /**
     * Map data to cells.
     */
    public function array(): array
    {
        $rows = [];
        foreach ($this->leaderboardData as $index => $row) {
            $rowData = [
                $index + 1, // Rank
                $row['operator_name'],
                $row['operator_code'],
                $row['average_kpi'] / 100, // Average KPI (decimal for percentage format)
                $row['working_days'], // Days
            ];

            foreach ($this->dates as $date) {
                $val = $row['matrix'][$date];
                // Convert percentage to decimal if not null, otherwise null for empty cell
                $rowData[] = $val !== null ? $val / 100 : null;
            }

            $rows[] = $rowData;
        }

        return $rows;
    }

    /**
     * Define column headings.
     */
    public function headings(): array
    {
        $headings = [
            'Rank',
            'Operator Name',
            'Operator Code',
            'Avg KPI',
            'Days',
        ];

        foreach ($this->dates as $date) {
            $headings[] = \Carbon\Carbon::parse($date)->translatedFormat('d M');
        }

        return $headings;
    }

    /**
     * Define number formatting for non-date columns.
     */
    public function columnFormats(): array
    {
        return [
            'D' => '0.0%', // Avg KPI
        ];
    }

    /**
     * Apply styles to sheet.
     */
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $fullRange = 'A1:' . $highestColumn . $highestRow;

        // Freeze pane below header
        $sheet->freezePane('A2');

        // Enable Auto Filter
        $sheet->setAutoFilter('A1:' . $highestColumn . '1');

        // Apply borders to all cells
        $sheet->getStyle($fullRange)->getBorders()->getAllBorders()->applyFromArray([
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'CCCCCC'],
        ]);

        // Style the header row
        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => '1E293B'], // dark gray text
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'F1F5F9'], // bg-slate-100
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Center alignments for Rank, Operator Code, Avg KPI, Working Days
        $sheet->getStyle('A2:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C2:C' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Explicitly format and align data cells for date matrix (from column F / 6 onwards, row 2 to highestRow)
        // This avoids applying the percentage format to the header row (row 1)
        $colIndex = 6;
        foreach ($this->dates as $date) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            
            $sheet->getStyle($colLetter . '2:' . $colLetter . $highestRow)
                ->getNumberFormat()
                ->setFormatCode('0%');
                
            $sheet->getStyle($colLetter . '2:' . $colLetter . $highestRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
            $colIndex++;
        }

        return [];
    }
}

