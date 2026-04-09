<?php

namespace App\Exports;

use App\Models\DowntimeLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DowntimeExport implements FromCollection, WithHeadings
{
    protected string $date;

    public function __construct(string $date)
    {
        $this->date = $date;
    }

    public function collection()
    {
        return DowntimeLog::whereDate('downtime_date', $this->date)
            ->select(
                'machine_code',
                'entry_type',
                'duration_minutes',
                'reason',
                'size_category',
                'rpm_feeding_mode',
                'rpm_value',
                'rpm_id_value',
                'feeding_value',
                'feeding_id_value',
                'check_cekam',
                'check_air_ozo',
                'check_eretan',
                'check_pisau',
                'check_kebersihan',
                'check_oli',
                'note'
            )
            ->orderBy('machine_code')
            ->get()
            ->map(function ($row) {
                return [
                    'machine' => strtoupper($row->machine_code),
                    'tipe' => $row->entry_type === 'check' ? 'Cek Harian' : 'Downtime',
                    'durasi' => $row->entry_type === 'downtime' ? $row->duration_minutes . ' min' : '-',
                    'alasan' => $row->entry_type === 'downtime' ? $row->reason : '-',
                    'cekam' => $row->entry_type === 'check' ? $row->check_cekam : '-',
                    'ozon' => $row->entry_type === 'check' ? $row->check_air_ozo : '-',
                    'eretan' => $row->entry_type === 'check' ? $row->check_eretan : '-',
                    'pisau' => $row->entry_type === 'check' ? $row->check_pisau : '-',
                    'bersih' => $row->entry_type === 'check' ? $row->check_kebersihan : '-',
                    'oli' => $row->entry_type === 'check' ? $row->check_oli : '-',
                    'size' => $row->entry_type === 'check' ? $row->size_category : '-',
                    'mode' => $row->entry_type === 'check' ? ucfirst($row->rpm_feeding_mode) : '-',
                    'rpm_samping' => $row->entry_type === 'check' ? $row->rpm_value : '-',
                    'rpm_id' => $row->entry_type === 'check' ? $row->rpm_id_value : '-',
                    'feeding_samping' => $row->entry_type === 'check' ? $row->feeding_value : '-',
                    'feeding_id' => $row->entry_type === 'check' ? $row->feeding_id_value : '-',
                    'catatan' => $row->note ?? '-',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Mesin',
            'Tipe',
            'Durasi',
            'Alasan/Masalah',
            'CEKAM',
            'OZON',
            'ERETAN',
            'PISAU',
            'BERSIH',
            'OLI',
            'Size (Check)',
            'Mode (Check)',
            'RPM Samping',
            'RPM ID',
            'Feeding Samping',
            'Feeding ID',
            'Catatan',
        ];
    }
}
