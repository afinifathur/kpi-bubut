<?php

namespace App\Http\Controllers;

use App\Models\DowntimeLog;
use Illuminate\Support\Facades\DB;

// MASTER MIRROR
use App\Models\MdMachine;

class TrackingDowntimeController extends Controller
{
    /**
     * List & summary downtime per tanggal
     */
    public function index()
    {
        /**
         * Ambil tanggal dari request
         * fallback ke tanggal downtime terbaru
         */
        $date = request('date') ?? DowntimeLog::max('downtime_date');

        if (!$date) {
            return back()->with('error', 'Tanggal downtime tidak ditemukan');
        }

        /**
         * List downtime (detail)
         */
        $list = DowntimeLog::whereDate('downtime_date', $date)
            ->orderBy('machine_code')
            ->orderBy('duration_minutes')
            ->get();

        /**
         * Summary downtime per mesin (total menit)
         */
        $summary = DowntimeLog::whereDate('downtime_date', $date)
            ->select(
                'machine_code',
                DB::raw('SUM(duration_minutes) as total_minutes')
            )
            ->groupBy('machine_code')
            ->orderBy('machine_code')
            ->get();

        /**
         * Mapping kode mesin → nama mesin
         */
        $machineNames = MdMachine::pluck('name', 'code');

        return view('downtime.index', [
            'list'         => $list,
            'summary'      => $summary,
            'machineNames' => $machineNames,
            'date'         => $date,
        ]);
    }
}
