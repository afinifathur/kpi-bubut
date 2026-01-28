<?php

namespace App\Http\Controllers;

use App\Models\DailyKpiOperator;
use App\Models\ProductionLog;

// MASTER MIRROR (READ ONLY - SSOT)
use App\Models\MdOperatorMirror;
use Barryvdh\DomPDF\Facade\Pdf;

class TrackingOperatorController extends Controller
{
    /**
     * ===============================
     * LIST KPI HARIAN OPERATOR
     * ===============================
     */
    /**
     * ===============================
     * LIST KPI HARIAN OPERATOR (REPORT GENERATOR)
     * ===============================
     */
    public function index()
    {
        $startDate = request('start_date') ?? date('Y-m-d');
        $endDate = request('end_date') ?? date('Y-m-d');
        $operatorCode = request('operator_code');

        // Validation 1: Max 45 Hari
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        $diff = $start->diffInDays($end);

        if ($diff > 45) {
            return redirect()->route('tracking.operator.index', [
                'start_date' => $startDate,
                'end_date' => $start->copy()->addDays(45)->format('Y-m-d'),
                'operator_code' => $operatorCode
            ])->with('error', 'Maksimal rentang tanggal adalah 45 hari. Tanggal akhir telah disesuaikan.');
        }

        // Validation 2: If > 1 day, Must select ONE operator
        if ($diff > 0 && (!$operatorCode || $operatorCode === 'all')) {
            // Fallback: If user tries to select all for range, force single day or show error
            // Here we'll just show error but technically UI should prevent it.
            // For safety, let's limit query to start date only if violation occurs, or show flash message.
            session()->flash('error', 'Untuk rentang lebih dari 1 hari, WAJIB pilih satu operator spesifik.');
            // Reset to single day to prevent crash/load
            $endDate = $startDate;
        }

        /**
         * Query Builder
         */
        $query = DailyKpiOperator::query();

        $query->whereBetween('kpi_date', [$startDate, $endDate]);

        if ($operatorCode && $operatorCode !== 'all') {
            $query->where('operator_code', $operatorCode);
        }

        // Get Data
        $rows = $query->orderBy('kpi_date', 'desc')
            ->orderBy('operator_code', 'asc')
            ->get();

        /**
         * Mapping kode operator -> nama operator
         */
        $operatorNames = MdOperatorMirror::orderBy('name')->pluck('name', 'code');

        return view('tracking.operator.index', [
            'rows' => $rows,
            'operatorNames' => $operatorNames,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedOperator' => $operatorCode,
        ]);
    }

    /**
     * ===============================
     * DETAIL KPI OPERATOR PER TANGGAL
     * ===============================
     */
    public function show(string $operatorCode, string $date)
    {
        /**
         * Summary KPI (IMMUTABLE FACT)
         */
        $summary = DailyKpiOperator::with('operator')
            ->where('operator_code', $operatorCode)
            ->where('kpi_date', $date)
            ->firstOrFail();

        /**
         * Detail aktivitas produksi (FACT LOG)
         */
        $activities = ProductionLog::with(['machine', 'item'])
            ->where('operator_code', $operatorCode)
            ->where('production_date', $date)
            ->orderBy('time_start')
            ->get();

        return view('tracking.operator.show', [
            'summary' => $summary,
            'activities' => $activities,
        ]);
    }
    /**
     * ===============================
     * EXPORT PDF
     * ===============================
     */
    public function exportPdf()
    {
        $startDate = request('start_date') ?? date('Y-m-d');
        $endDate = request('end_date') ?? date('Y-m-d');
        $operatorCode = request('operator_code');

        // Validation (Mirror Index)
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        $diff = $start->diffInDays($end);

        if ($diff > 45) {
            return redirect()->back()->with('error', 'Rentang tanggal terlalu panjang (Max 45 hari).');
        }
        if ($diff > 0 && (!$operatorCode || $operatorCode === 'all')) {
            return redirect()->back()->with('error', 'Untuk export > 1 hari, WAJIB pilih operator.');
        }

        // Query Data Source (ProductionLog or DailyKpiOperator?)
        // The original exportPdf used ProductionLog to show detailed rows.
        // If we are generating a report for a range, maybe we want summary? 
        // User said "generate data operator 0942 from 1-31 Jan". Usually implies detailed logs OR summary list.
        // Given the original PDF was detailed rows, let's keep it detailed but filtered.

        $query = ProductionLog::with(['machine', 'item', 'operator']);
        $query->whereBetween('production_date', [$startDate, $endDate]);

        if ($operatorCode && $operatorCode !== 'all') {
            $query->where('operator_code', $operatorCode);
        }

        $rows = $query->orderBy('production_date')
            ->orderBy('shift')
            ->orderBy('operator_code')
            ->orderBy('time_start')
            ->get();

        $operatorNames = MdOperatorMirror::pluck('name', 'code');

        // We might need a slightly different view or pass range info
        $pdf = Pdf::loadView('tracking.operator.pdf', [
            'rows' => $rows,
            'operatorNames' => $operatorNames,
            'date' => ($startDate === $endDate) ? $startDate : "$startDate - $endDate", // Label
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream('Laporan-Operator-' . $operatorCode . '-' . $startDate . '-to-' . $endDate . '.pdf');
    }
}
