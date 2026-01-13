<?php

namespace App\Services;

use App\Models\ProductionLog;
use App\Models\DailyKpiOperator;
use App\Models\DailyKpiMachine;
use Illuminate\Support\Facades\DB;

class DailyKpiService
{
    /**
     * Generate KPI harian per Operator
     */
    public static function generateOperatorDaily(string $date): void
    {
        $rows = ProductionLog::where('production_date', $date)
            ->select(
                'department_code',
                'operator_code',
                DB::raw('SUM(work_hours) as total_work_hours'),
                DB::raw('SUM(target_qty) as total_target_qty'),
                DB::raw('SUM(actual_qty) as total_actual_qty')
            )
            ->groupBy('department_code', 'operator_code')
            ->get();

        foreach ($rows as $row) {

            $kpiPercent = $row->total_target_qty > 0
                ? round(($row->total_actual_qty / $row->total_target_qty) * 100, 2)
                : 0;

            DailyKpiOperator::updateOrCreate(
                [
                    'kpi_date' => $date,
                    'department_code' => $row->department_code,
                    'operator_code' => $row->operator_code,
                ],
                [
                    'total_work_hours' => $row->total_work_hours,
                    'total_target_qty' => $row->total_target_qty,
                    'total_actual_qty' => $row->total_actual_qty,
                    'kpi_percent' => $kpiPercent,
                ]
            );
        }
    }

    /**
     * Generate KPI harian per Mesin
     */
    public static function generateMachineDaily(string $date): void
    {
        $rows = ProductionLog::where('production_date', $date)
            ->select(
                'department_code',
                'machine_code',
                DB::raw('SUM(work_hours) as total_work_hours'),
                DB::raw('SUM(target_qty) as total_target_qty'),
                DB::raw('SUM(actual_qty) as total_actual_qty')
            )
            ->groupBy('department_code', 'machine_code')
            ->get();

        foreach ($rows as $row) {

            $kpiPercent = $row->total_target_qty > 0
                ? round(($row->total_actual_qty / $row->total_target_qty) * 100, 2)
                : 0;

            DailyKpiMachine::updateOrCreate(
                [
                    'kpi_date' => $date,
                    'department_code' => $row->department_code,
                    'machine_code' => $row->machine_code,
                ],
                [
                    'total_work_hours' => $row->total_work_hours,
                    'total_target_qty' => $row->total_target_qty,
                    'total_actual_qty' => $row->total_actual_qty,
                    'kpi_percent' => $kpiPercent,
                ]
            );
        }
    }
}

