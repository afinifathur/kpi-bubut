<?php

namespace App\Http\Controllers;

use App\Models\DailyKpiMachine;
use App\Models\ProductionLog;
use App\Models\MdMachine;

class TrackingMachineController extends Controller
{
    /**
     * List KPI harian mesin
     */
    public function index()
    {
        // Ambil tanggal dari request atau fallback ke KPI terakhir
        $date = request('date')
            ?? DailyKpiMachine::max('kpi_date');

        if (!$date) {
            return back()->with('error', 'Tanggal tidak ditemukan');
        }

        // KPI mesin (SUMBER RESMI)
        $rows = DailyKpiMachine::where('kpi_date', $date)
            ->orderBy('machine_code')
            ->get();

        // Mapping kode mesin → nama
        $machineNames = MdMachine::pluck('name', 'code');

        return view('tracking.machine.index', [
            'rows'         => $rows,
            'machineNames' => $machineNames,
            'date'         => $date,
        ]);
    }

    /**
     * Detail KPI mesin per tanggal
     */
    public function show(string $machine, string $date)
    {
        // Summary KPI mesin
        $summary = DailyKpiMachine::where('machine_code', $machine)
            ->where('kpi_date', $date)
            ->firstOrFail();

        // Detail aktivitas produksi (drill-down)
        $activities = ProductionLog::where('machine_code', $machine)
            ->where('production_date', $date)
            ->orderBy('time_start')
            ->get();

        return view('tracking.machine.show', [
            'summary'    => $summary,
            'activities' => $activities,
            'machine'    => $machine,
            'date'       => $date,
        ]);
    }
}
