<?php

namespace App\Reports;

use App\Models\DailyKpiOperator;
use App\Models\MdOperatorMirror;
use App\Models\MdDepartment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class LeaderboardReport
{
    protected string $startDate;
    protected string $endDate;
    protected ?string $operatorCode = null;
    protected ?string $departmentCode = null;
    protected ?string $departmentName = null;

    /**
     * LeaderboardReport constructor.
     */
    public function __construct(Request $request)
    {
        $this->endDate = $request->get('end_date', date('Y-m-d'));
        $this->startDate = $request->get('start_date', Carbon::parse($this->endDate)->subDays(30)->format('Y-m-d'));
        $this->operatorCode = $request->get('operator_code');

        // Resolve Active Department context
        $user = auth()->user();
        $deptCode = session('selected_department_code');
        if (empty($deptCode) || $deptCode === 'all') {
            $deptCode = $user->department_code ?? null;
        }
        $this->departmentCode = $deptCode;

        if ($this->departmentCode) {
            $this->departmentName = MdDepartment::where('code', $this->departmentCode)->value('name') ?? $this->departmentCode;
        }
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getEndDate(): string
    {
        return $this->endDate;
    }

    public function getSelectedOperator(): ?string
    {
        return $this->operatorCode;
    }

    public function getDepartmentCode(): ?string
    {
        return $this->departmentCode;
    }

    public function getDepartmentName(): ?string
    {
        return $this->departmentName ?? 'All Departments';
    }

    /**
     * Get the list of dates in the range.
     */
    public function getDates(): array
    {
        $period = CarbonPeriod::create($this->startDate, $this->endDate);
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }
        return $dates;
    }

    /**
     * Get the operator names for the dropdown.
     */
    public function getOperatorNames(): \Illuminate\Support\Collection
    {
        return MdOperatorMirror::orderBy('name')->pluck('name', 'code');
    }

    /**
     * Execute query and get sorted leaderboard data.
     */
    public function getData(): array
    {
        $dates = $this->getDates();

        // The DailyKpiOperator model automatically applies the global DepartmentScope
        $query = DailyKpiOperator::with('operator')
            ->whereBetween('kpi_date', [$this->startDate, $this->endDate]);

        if ($this->operatorCode && $this->operatorCode !== 'all') {
            $query->where('operator_code', $this->operatorCode);
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

        return $leaderboardData;
    }
}
