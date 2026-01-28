<?php

namespace App\Http\Controllers;

use App\Models\DailyKpiMachine;
use App\Models\ProductionLog;

// MASTER MIRROR (READ ONLY - SSOT)
use App\Models\MdMachineMirror;
use Barryvdh\DomPDF\Facade\Pdf;

class TrackingMachineController extends Controller
{
    /**
     * ===============================
     * LIST KPI HARIAN MESIN
     * ===============================
     */
    public function index()
    {
        $startDate = request('start_date', date('Y-m-d'));
        $endDate = request('end_date', date('Y-m-d'));
        $machineCode = request('machine_code');

        // VALIDATION LOGIC
        // 1. Max Range 45 Days
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        if ($start->diffInDays($end) > 45) {
            return redirect()->route('tracking.mesin.index', ['date' => $startDate])
                ->with('error', 'Rentang tanggal maksimal 45 hari per 1 mesin.');
        }

        // 2. Mandatory Machine for Range > 1 Day
        // If diff > 0 (meaning more than 1 day span) and no specific machine (or 'all'), reject.
        if ($start->diffInDays($end) > 0 && (!$machineCode || $machineCode === 'all')) {
            return redirect()->route('tracking.mesin.index', ['date' => $endDate])
                ->with('error', 'Untuk rentang tanggal lebih dari 1 hari, Anda WAJIB memilih 1 mesin spesifik.');
        }

        $query = DailyKpiMachine::query();

        // Filter Date Range
        $query->whereBetween('kpi_date', [$startDate, $endDate]);

        // Filter Machine
        if ($machineCode && $machineCode !== 'all') {
            $query->where('machine_code', $machineCode);
        }

        $rows = $query->orderBy('kpi_date', 'desc')
            ->orderBy('machine_code', 'asc')
            ->get();

        $machineNames = MdMachineMirror::pluck('name', 'code');

        // Shifts logic - might be heavy for range, keep it simple or optimize
        // For report view, maybe we just list shifts if manageable, or skip complex pivot
        // Let's keep existing logic but careful with big data. 
        // Actually, existing logic used ProductionLog specific to date. 
        // We'll skip shift mapping for range view to save performance or query it if filtering by 1 day.
        $shifts = [];
        if ($startDate === $endDate) {
            $shifts = ProductionLog::where('production_date', $startDate)
                ->select('machine_code', 'shift')
                ->distinct()
                ->get()
                ->groupBy('machine_code')
                ->map(function ($items) {
                    return $items->pluck('shift')->implode(', ');
                });
        }

        return view('tracking.machine.index', [
            'rows' => $rows,
            'machineNames' => $machineNames,
            'shifts' => $shifts,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedMachine' => $machineCode,
            'date' => $endDate, // fallback compatibility
        ]);
    }

    /**
     * ===============================
     * DETAIL KPI MESIN PER TANGGAL
     * ===============================
     */
    public function show(string $machineCode, string $date)
    {
        /**
         * Summary KPI mesin (IMMUTABLE FACT)
         */
        $summary = DailyKpiMachine::with('machine')
            ->where('machine_code', $machineCode)
            ->where('kpi_date', $date)
            ->firstOrFail();

        /**
         * Detail aktivitas produksi (FACT LOG)
         */
        $activities = ProductionLog::with(['operator', 'item'])
            ->where('machine_code', $machineCode)
            ->where('production_date', $date)
            ->orderBy('time_start')
            ->get();

        return view('tracking.machine.show', [
            'summary' => $summary,
            'activities' => $activities,
            'machine' => $machineCode,
            'date' => $date,
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
        $machineCode = request('machine_code');

        // VALIDATION
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        if ($start->diffInDays($end) > 45) {
            return back()->with('error', 'Rentang tanggal maksimal 45 hari.');
        }
        if ($start->diffInDays($end) > 0 && (!$machineCode || $machineCode === 'all')) {
            return back()->with('error', 'Wajib pilih mesin untuk rentang > 1 hari.');
        }

        $query = ProductionLog::with(['operator', 'item'])
            ->whereBetween('production_date', [$startDate, $endDate]);

        if ($machineCode && $machineCode !== 'all') {
            $query->where('machine_code', $machineCode);
        }

        $rows = $query->orderBy('production_date', 'asc')
            ->orderBy('machine_code', 'asc')
            ->orderBy('shift')
            ->get();

        $machineNames = MdMachineMirror::pluck('name', 'code');
        $dateLabel = ($startDate === $endDate) ? $startDate : "$startDate - $endDate";

        $pdf = Pdf::loadView('tracking.machine.pdf', [
            'rows' => $rows,
            'machineNames' => $machineNames,
            'date' => $dateLabel,
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream('KPI-Mesin-' . $dateLabel . '.pdf');
    }
}
