<?php
namespace App\Http\Controllers;

use App\Exports\OperatorKpiExport;
use App\Exports\MachineKpiExport;
use App\Exports\DowntimeExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function operatorKpi()
    {
        $startDate = request('start_date', date('Y-m-d'));
        $endDate = request('end_date', date('Y-m-d'));
        $operatorCode = request('operator_code');

        // Sanitize "all" to null
        if ($operatorCode === 'all') {
            $operatorCode = null;
        }

        $suffix = $operatorCode ? "{$operatorCode}_" : 'all_';
        $filename = "kpi_operator_{$suffix}{$startDate}_to_{$endDate}.xlsx";

        return Excel::download(
            new OperatorKpiExport($startDate, $endDate, $operatorCode),
            $filename
        );
    }

    public function machineKpi(?string $date = null)
    {
        $startDate = request('start_date', $date ?? date('Y-m-d'));
        $endDate = request('end_date', $date ?? date('Y-m-d'));
        $machineCode = request('machine_code');

        // Sanitize "all" to null
        if ($machineCode === 'all') {
            $machineCode = null;
        }

        $suffix = $machineCode ? "{$machineCode}_" : 'all_';
        $filename = "kpi_machine_{$suffix}{$startDate}_to_{$endDate}.xlsx";

        return Excel::download(
            new MachineKpiExport($startDate, $endDate, $machineCode),
            $filename
        );
    }

    public function downtime(string $date)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new DowntimeExport($date),
            "downtime_{$date}.xlsx"
        );
    }
}
