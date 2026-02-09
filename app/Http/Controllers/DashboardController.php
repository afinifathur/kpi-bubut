<?php

namespace App\Http\Controllers;

use App\Models\MdMachineMirror;

class DashboardController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | DATE & SCOPE
        |--------------------------------------------------------------------------
        */
        // Use latest Production Log date or yesterday as fallback
        $date = \App\Models\ProductionLog::max('production_date')
            ?? \Carbon\Carbon::yesterday()->format('Y-m-d');

        $prevDate = \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | 1. CARD STATS (Daily Aggregate - from Raw Logs for accuracy)
        |--------------------------------------------------------------------------
        */
        $dailyStats = \App\Models\ProductionLog::where('production_date', $date)
            ->selectRaw('
                COALESCE(SUM(target_qty), 0) as total_target,
                COALESCE(SUM(actual_qty), 0) as total_actual
            ')
            ->first();

        // Calculate Efficiency safely
        $efficiency = $dailyStats->total_target > 0
            ? ($dailyStats->total_actual / $dailyStats->total_target) * 100
            : 0;

        // Overall KPI (Average of all operators - from Raw Logs)
        // Logic: (Sum Actual / Sum Target) per operator, then average
        $operatorKpis = \App\Models\ProductionLog::where('production_date', $date)
            ->selectRaw('operator_code, (SUM(actual_qty) / NULLIF(SUM(target_qty), 0)) * 100 as kpi')
            ->groupBy('operator_code')
            ->get();

        $overallKpi = $operatorKpis->avg('kpi') ?? 0;

        /*
        |--------------------------------------------------------------------------
        | 2. CHARTS DATA
        |--------------------------------------------------------------------------
        */

        // A. Last 7 Active Production Days (Skip empty dates)
        $activeDates = \App\Models\ProductionLog::select('production_date')
            ->distinct()
            ->orderByDesc('production_date')
            ->limit(7)
            ->pluck('production_date')
            ->sort()
            ->values()
            ->toArray();

        $weeklyProduction = \App\Models\ProductionLog::selectRaw('production_date as kpi_date, SUM(actual_qty) as total_actual, SUM(target_qty) as total_target')
            ->whereIn('production_date', $activeDates)
            ->groupBy('production_date')
            ->orderBy('production_date')
            ->get();

        // B. Production by Line (Last 7 Active Days) - DYNAMIC LINES
        $productionByLine = \App\Models\ProductionLog::selectRaw('production_date, line, SUM(actual_qty) as total_qty')
            ->whereIn('production_date', $activeDates)
            ->whereNotNull('line')
            ->groupBy('production_date', 'line')
            ->orderBy('production_date')
            ->get();

        // Transform for Chart.js: [ '2023-01-01' => ['Line 1' => 100, 'Line 2' => 50] ]
        $lineChartData = [];
        $allLines = [];

        foreach ($productionByLine as $record) {
            $d = $record->production_date;
            $l = $record->line;
            $q = (int) $record->total_qty;

            if (!isset($lineChartData[$d])) {
                $lineChartData[$d] = [];
            }
            $lineChartData[$d][$l] = $q;

            if (!in_array($l, $allLines)) {
                $allLines[] = $l;
            }
        }
        sort($allLines); // Ensure consistent order (Line 1, Line 2...)

        // C. Top 3 Reject Reasons (Current Month)
        // Note: RejectLog uses 'reject_date'
        $monthStart = \Carbon\Carbon::parse($date)->startOfMonth()->format('Y-m-d');
        $monthEnd = \Carbon\Carbon::parse($date)->endOfMonth()->format('Y-m-d');

        // Label for View: "1 - 27 Januari 2026"
        $monthLabel = \Carbon\Carbon::parse($monthStart)->format('j') . ' - ' .
            \Carbon\Carbon::parse($date)->translatedFormat('j F Y');

        $rejectAnalysis = \App\Models\RejectLog::selectRaw('reject_reason, SUM(reject_qty) as total_qty')
            ->whereBetween('reject_date', [$monthStart, $monthEnd])
            ->groupBy('reject_reason')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // D. Top 3 Operators (Monthly Average - from Raw Logs)
        $topOperators = \App\Models\ProductionLog::selectRaw('operator_code, (SUM(actual_qty) / NULLIF(SUM(target_qty), 0)) * 100 as kpi_percent')
            ->whereBetween('production_date', [$monthStart, $monthEnd])
            ->groupBy('operator_code')
            ->orderByDesc('kpi_percent')
            ->limit(3)
            ->get();

        // Map Operator Names found in Mirror
        $operatorCodes = $topOperators->pluck('operator_code');
        // Merge with Low performing codes later or query separate?
        // Query separate to ensure we have names for both lists

        // E. Low Performing Operators (Monthly Average < 90% - from Raw Logs)
        $lowOperators = \App\Models\ProductionLog::selectRaw('operator_code, (SUM(actual_qty) / NULLIF(SUM(target_qty), 0)) * 100 as kpi_percent')
            ->whereBetween('production_date', [$monthStart, $monthEnd])
            ->groupBy('operator_code')
            ->having('kpi_percent', '<', 90)
            ->orderBy('kpi_percent')
            ->limit(3)
            ->get();

        // Combine codes to fetch names in one go
        $allOpCodes = $operatorCodes->merge($lowOperators->pluck('operator_code'))->unique();

        $operatorNames = \App\Models\MdOperatorMirror::whereIn('code', $allOpCodes)
            ->pluck('name', 'code');

        /*
        |--------------------------------------------------------------------------
        | MACHINE STATUS (ACTIVE ONLY)
        |--------------------------------------------------------------------------
        */
        // Existing Logic Preserved
        $machines = MdMachineMirror::where('status', 'active')
            ->orderBy('department_code')
            ->orderBy('code')
            ->get();

        $machineSummary = [
            'ONLINE' => MdMachineMirror::where('status', 'active')->where('runtime_status', 'ONLINE')->count(),
            'STALE' => MdMachineMirror::where('status', 'active')->where('runtime_status', 'STALE')->count(),
            'OFFLINE' => MdMachineMirror::where('status', 'active')->where('runtime_status', 'OFFLINE')->count(),
        ];

        return view('dashboard.index', compact(
            'date',
            'dailyStats',
            'efficiency',
            'overallKpi',
            'weeklyProduction',
            'lineChartData', // New passed variable
            'allLines',      // New passed variable
            'monthLabel',    // New: Date Range Context
            'rejectAnalysis',
            'topOperators',
            'lowOperators',
            'operatorNames',
            'machines',
            'machineSummary'
        ));
    }
}
