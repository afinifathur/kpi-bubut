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
        // Use latest KPI date or yesterday as fallback
        $date = \App\Models\DailyKpiOperator::max('kpi_date')
            ?? \Carbon\Carbon::yesterday()->format('Y-m-d');

        $prevDate = \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | 1. CARD STATS (Daily Aggregate)
        |--------------------------------------------------------------------------
        */
        $dailyStats = \App\Models\DailyKpiOperator::where('kpi_date', $date)
            ->selectRaw('
                COALESCE(SUM(total_target_qty), 0) as total_target,
                COALESCE(SUM(total_actual_qty), 0) as total_actual
            ')
            ->first();

        // Calculate Efficiency safely
        $efficiency = $dailyStats->total_target > 0
            ? ($dailyStats->total_actual / $dailyStats->total_target) * 100
            : 0;

        // Overall KPI (Average of all operators)
        $overallKpi = \App\Models\DailyKpiOperator::where('kpi_date', $date)
            ->avg('kpi_percent') ?? 0;

        /*
        |--------------------------------------------------------------------------
        | 2. CHARTS DATA
        |--------------------------------------------------------------------------
        */

        // A. Weekly Production (Last 7 Days)
        $weeklyProduction = \App\Models\DailyKpiOperator::selectRaw('kpi_date, SUM(total_actual_qty) as total_actual, SUM(total_target_qty) as total_target')
            ->where('kpi_date', '>=', \Carbon\Carbon::parse($date)->subDays(6))
            ->where('kpi_date', '<=', $date)
            ->groupBy('kpi_date')
            ->orderBy('kpi_date')
            ->get();

        // B. Top 3 Reject Reasons (Current Month)
        // Note: RejectLog uses 'reject_date'
        $monthStart = \Carbon\Carbon::parse($date)->startOfMonth()->format('Y-m-d');
        $monthEnd = \Carbon\Carbon::parse($date)->endOfMonth()->format('Y-m-d');

        $rejectAnalysis = \App\Models\RejectLog::selectRaw('reject_reason, SUM(reject_qty) as total_qty')
            ->whereBetween('reject_date', [$monthStart, $monthEnd])
            ->groupBy('reject_reason')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // C. Top 3 Operators (Daily)
        $topOperators = \App\Models\DailyKpiOperator::where('kpi_date', $date)
            ->orderByDesc('kpi_percent')
            ->limit(3)
            ->get();

        // Map Operator Names found in Mirror
        $operatorCodes = $topOperators->pluck('operator_code');
        $operatorNames = \App\Models\MdOperatorMirror::whereIn('code', $operatorCodes)
            ->pluck('name', 'code');

        // D. Low Performing Operators (Attention needed) < 90%
        $lowOperators = \App\Models\DailyKpiOperator::where('kpi_date', $date)
            ->where('kpi_percent', '<', 90)
            ->orderBy('kpi_percent')
            ->limit(3)
            ->get();

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
            'rejectAnalysis',
            'topOperators',
            'lowOperators',
            'operatorNames',
            'machines',
            'machineSummary'
        ));
    }
}
