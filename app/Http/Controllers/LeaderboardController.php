<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyKpiOperator;
use App\Models\MdOperatorMirror;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaderboardController extends Controller
{
    /**
     * Display the operator leaderboard.
     */
    public function index(Request $request)
    {
        $endDate = $request->get('end_date', date('Y-m-d'));
        $startDate = $request->get('start_date', Carbon::parse($endDate)->subDays(30)->format('Y-m-d'));
        $operatorCode = $request->get('operator_code');

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            return redirect()->route('leaderboard.index', [
                'start_date' => $endDate,
                'end_date' => $startDate,
                'operator_code' => $operatorCode
            ])->with('error', 'Tanggal mulai tidak boleh melebihi tanggal akhir.');
        }

        // Validate max range: 366 days
        if ($start->diffInDays($end) > 366) {
            return redirect()->route('leaderboard.index', [
                'start_date' => $startDate,
                'end_date' => $start->copy()->addDays(366)->format('Y-m-d'),
                'operator_code' => $operatorCode
            ])->with('error', 'Maksimal rentang tanggal adalah 366 hari. Tanggal akhir telah disesuaikan.');
        }

        // Generate the array of dates for the matrix columns
        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        // Query daily KPI records for the selected period
        // The DailyKpiOperator model automatically applies the global DepartmentScope
        $query = DailyKpiOperator::with('operator')
            ->whereBetween('kpi_date', [$startDate, $endDate]);

        if ($operatorCode && $operatorCode !== 'all') {
            $query->where('operator_code', $operatorCode);
        }

        $records = $query->get();

        // Group by operator
        $grouped = $records->groupBy('operator_code');
        
        $leaderboardData = [];

        foreach ($grouped as $opCode => $opRecords) {
            $recordsByDate = $opRecords->keyBy('kpi_date');
            
            // Calculate average of available KPI values
            $activeDaysCount = $opRecords->count();
            $sumKpi = $opRecords->sum('kpi_percent');
            $averageKpi = $activeDaysCount > 0 ? ($sumKpi / $activeDaysCount) : 0;

            // Build matrix of daily KPIs
            $matrix = [];
            foreach ($dates as $dateStr) {
                if (isset($recordsByDate[$dateStr])) {
                    $matrix[$dateStr] = $recordsByDate[$dateStr]->kpi_percent;
                } else {
                    $matrix[$dateStr] = null;
                }
            }

            // Get operator name from eager-loaded relationship or fall back to code
            $operatorName = $opRecords->first()->operator->name ?? $opCode;

            $leaderboardData[] = [
                'operator_code' => $opCode,
                'operator_name' => $operatorName,
                'average_kpi' => $averageKpi,
                'working_days' => $activeDaysCount,
                'matrix' => $matrix,
            ];
        }

        // Sort descending by average KPI with tie-breakers:
        // 1. Higher Working Days (descending)
        // 2. Operator Name (ascending)
        usort($leaderboardData, function ($a, $b) {
            if (abs($b['average_kpi'] - $a['average_kpi']) > 0.0001) {
                return $b['average_kpi'] <=> $a['average_kpi'];
            }
            
            if ($b['working_days'] !== $a['working_days']) {
                return $b['working_days'] <=> $a['working_days'];
            }

            return strcmp($a['operator_name'], $b['operator_name']);
        });

        // Get list of all operators for the dropdown filter (same as KPI Trend)
        $operatorNames = MdOperatorMirror::orderBy('name')->pluck('name', 'code');

        return view('leaderboard.index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedOperator' => $operatorCode,
            'operatorNames' => $operatorNames,
            'dates' => $dates,
            'leaderboardData' => $leaderboardData,
        ]);
    }
}
