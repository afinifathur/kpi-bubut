<?php

namespace App\Http\Controllers;

use App\Models\DowntimeLog;
use Illuminate\Support\Facades\DB;

// MASTER MIRROR (READ ONLY - SSOT)
use App\Models\MdMachineMirror;
use Barryvdh\DomPDF\Facade\Pdf;

class TrackingDowntimeController extends Controller
{
    /**
     * ===============================
     * LIST & SUMMARY DOWNTIME PER TANGGAL
     * ===============================
     */
    public function index()
    {
        $startDate = request('start_date', date('Y-m-d'));
        $endDate = request('end_date', date('Y-m-d'));
        $machineCode = request('machine_code');

        // Validation 1: Max 45 Hari
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        if ($start->diffInDays($end) > 45) {
            return redirect()->route('downtime.tracking', ['date' => $startDate])
                ->with('error', 'Rentang tanggal maksimal 45 hari. Silakan perkecil rentang.');
        }

        // Validation 2: Machine Mandatory if > 1 day
        if ($start->diffInDays($end) > 0 && (!$machineCode || $machineCode === 'all')) {
            return redirect()->route('downtime.tracking', ['date' => $endDate])
                ->with('error', 'Untuk rentang tanggal > 1 hari, WAJIB memilih satu mesin spesifik.');
        }

        /**
         * LIST DOWNTIME (DETAIL EVENT)
         * FACT TABLE — READ ONLY
         */
        $queryList = DowntimeLog::with(['machine', 'operator'])
            ->whereBetween('downtime_date', [$startDate, $endDate]);

        if ($machineCode && $machineCode !== 'all') {
            $queryList->where('machine_code', $machineCode);
        }

        $list = $queryList->orderBy('downtime_date', 'desc')
            ->orderBy('machine_code')
            ->orderByDesc('duration_minutes')
            ->get();

        /**
         * SUMMARY DOWNTIME PER MESIN (TOTAL MENIT)
         * AGGREGATE FACT
         */
        $querySummary = DowntimeLog::whereBetween('downtime_date', [$startDate, $endDate]);

        if ($machineCode && $machineCode !== 'all') {
            $querySummary->where('machine_code', $machineCode);
        }

        $summary = $querySummary->select(
            'machine_code',
            DB::raw('SUM(duration_minutes) as total_minutes')
        )
            ->groupBy('machine_code')
            ->orderBy('machine_code')
            ->get();

        /**
         * Mapping kode mesin -> nama mesin
         * Mirror master (READ ONLY)
         */
        $machineNames = MdMachineMirror::pluck('name', 'code');

        return view('downtime.index', [
            'list' => $list,
            'summary' => $summary,
            'machineNames' => $machineNames,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedMachine' => $machineCode,
            'date' => $endDate, // fallback compatibility
        ]);
    }
    /**
     * ===============================
     * EXPORT PDF
     * ===============================
     */
    public function exportPdf()
    {
        $startDate = request('start_date', date('Y-m-d'));
        $endDate = request('end_date', date('Y-m-d'));
        $machineCode = request('machine_code');

        // Validation
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        if ($start->diffInDays($end) > 45) {
            return back()->with('error', 'Rentang tanggal maksimal 45 hari.');
        }
        if ($start->diffInDays($end) > 0 && (!$machineCode || $machineCode === 'all')) {
            return back()->with('error', 'Wajib pilih mesin untuk rentang > 1 hari.');
        }

        // QUERY LIST
        $queryList = DowntimeLog::with(['machine', 'operator'])
            ->whereBetween('downtime_date', [$startDate, $endDate]);

        if ($machineCode && $machineCode !== 'all') {
            $queryList->where('machine_code', $machineCode);
        }

        $list = $queryList->orderBy('downtime_date')
            ->orderBy('machine_code')
            ->orderByDesc('duration_minutes')
            ->get();

        // QUERY SUMMARY
        $querySummary = DowntimeLog::whereBetween('downtime_date', [$startDate, $endDate]);

        if ($machineCode && $machineCode !== 'all') {
            $querySummary->where('machine_code', $machineCode);
        }

        $summary = $querySummary->select(
            'machine_code',
            DB::raw('SUM(duration_minutes) as total_minutes')
        )
            ->groupBy('machine_code')
            ->orderBy('machine_code')
            ->get();

        $machineNames = MdMachineMirror::pluck('name', 'code');
        $dateLabel = ($startDate === $endDate) ? $startDate : "$startDate - $endDate";

        $pdf = Pdf::loadView('downtime.pdf', [
            'list' => $list,
            'summary' => $summary,
            'machineNames' => $machineNames,
            'date' => $dateLabel,
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream('Laporan-Downtime-' . $dateLabel . '.pdf');
    }
}
