<?php

namespace App\Services;

use App\Models\ProductionLog;
use App\Models\RejectLog;
use App\Models\DowntimeLog;
use Illuminate\Support\Facades\DB;

class OeeService
{
    /**
     * Orchestrates OEE data compilation over the date range
     */
    public function getOeeReport(
        string $startDate,
        string $endDate,
        string $departmentCode,
        ?string $machineCode = null
    ): array {
        // 1. Fetch Planned Capacities to build override maps in-memory
        $plannedCapacities = $this->getPlannedCapacities($startDate, $endDate, $departmentCode);

        $machineOverrides = [];
        $globalOverrides = [];

        foreach ($plannedCapacities as $cap) {
            $dateStr = is_string($cap->work_date) ? $cap->work_date : $cap->work_date->format('Y-m-d');
            $mCode = strtolower(trim($cap->machine_code));
            $totalHours = (float)$cap->shift_1_hours + (float)$cap->shift_2_hours + (float)$cap->shift_3_hours;

            if ($mCode === 'global') {
                $globalOverrides[$dateStr] = $totalHours;
            } else {
                $machineOverrides["{$dateStr}_{$mCode}"] = $totalHours;
            }
        }

        // 2. Run direct SQL aggregates
        $productionData = $this->aggregateProduction($startDate, $endDate, $departmentCode, $machineCode);
        $rejectData = $this->aggregateRejects($startDate, $endDate, $departmentCode, $machineCode);
        $downtimeData = $this->aggregateDowntime($startDate, $endDate, $departmentCode, $machineCode);

        // 3. Stitch data by date + machine keys present in productionData
        // (Rule 11: Empty Day Rule - Days without production logs DO NOT generate rows)
        $rows = [];
        $totalWorkHours = 0.0;
        $totalDowntimeHours = 0.0;
        $totalActualQty = 0;
        $totalTargetQty = 0;
        $totalRejectQty = 0;

        $globalCappedRuntime = 0.0;
        $globalCappedActualQty = 0;
        $globalCappedTargetQty = 0;
        $globalCappedGoodOutput = 0;
        $globalPlannedCapacity = 0.0;
        $globalWorkHoursForSummary = 0.0;
        $globalActualQtyForSummary = 0;

        foreach ($productionData as $date => $machines) {
            $dayWorkHours = 0.0;
            $dayDowntimeHours = 0.0;
            $dayActualQty = 0;
            $dayTargetQty = 0;
            $dayRejectQty = 0;

            $dayCappedRuntime = 0.0;
            $dayCappedActualQty = 0;
            $dayCappedTargetQty = 0;
            $dayCappedGoodOutput = 0;
            $dayPlannedCapacity = 0.0;

            foreach ($machines as $mCode => $prod) {
                $mWorkHours = $prod['work_hours'];
                $mTargetQty = $prod['target_qty'];
                $mActualQty = $prod['actual_qty'];

                $mRejectQty = isset($rejectData[$date][$mCode]) ? $rejectData[$date][$mCode]['reject_qty'] : 0;
                $mDowntimeHours = isset($downtimeData[$date][$mCode]) ? $downtimeData[$date][$mCode]['downtime_hours'] : 0.0;

                // Task 4: Negative Runtime Fix (capping runtime at >= 0 for each machine-day)
                $mRuntime = max(0.0, $mWorkHours - $mDowntimeHours);

                // Task 3: Performance Inflation Fix (capping actual quantity at target quantity for each machine-day)
                $mEffectiveActual = min($mActualQty, $mTargetQty);

                // Quality metric good output calculation per machine-day:
                $mGoodOutput = max(0, $mActualQty - $mRejectQty);

                // Resolve Capacity Resolution Hierarchy (Rules #1, #2, #3, #4, #5)
                $resolvedCapacity = 21.0;
                $capacitySource = 'DEFAULT';

                if (isset($machineOverrides["{$date}_{$mCode}"])) {
                    $resolvedCapacity = $machineOverrides["{$date}_{$mCode}"];
                    $capacitySource = 'MACHINE';
                } elseif (isset($globalOverrides[$date])) {
                    $resolvedCapacity = $globalOverrides[$date];
                    $capacitySource = 'GLOBAL';
                }

                // Log internally for debugging/audit verification: Date, Machine, Resolved Capacity, Capacity Source
                \Illuminate\Support\Facades\Log::debug(sprintf(
                    "OeeService Capacity [Date: %s, Machine: %s, Resolved Capacity: %.2f, Source: %s]",
                    $date, $mCode, $resolvedCapacity, $capacitySource
                ));

                // Accumulate daily raw totals
                $dayWorkHours += $mWorkHours;
                $dayDowntimeHours += $mDowntimeHours;
                $dayActualQty += $mActualQty;
                $dayTargetQty += $mTargetQty;
                $dayRejectQty += $mRejectQty;

                // Capped values accumulation (only contribute to OEE factors if capacity > 0)
                if ($resolvedCapacity > 0.0) {
                    $dayCappedRuntime += $mRuntime;
                    $dayCappedActualQty += $mEffectiveActual;
                    $dayCappedTargetQty += $mTargetQty;
                    $dayCappedGoodOutput += $mGoodOutput;
                    $dayPlannedCapacity += $resolvedCapacity;
                }
            }

            // Compute daily factors based on capped parameters
            if ($dayPlannedCapacity > 0.0) {
                $availability = min(1.0, max(0.0, $dayCappedRuntime / $dayPlannedCapacity));
                $performance = $dayCappedTargetQty > 0 ? min(1.0, max(0.0, $dayCappedActualQty / $dayCappedTargetQty)) : 0.0;
                $quality = $dayActualQty > 0 ? min(1.0, max(0.0, $dayCappedGoodOutput / $dayActualQty)) : 0.0;
                $oee = min(1.0, max(0.0, $availability * $performance * $quality));
            } else {
                // Non-Working Day Rule: Capacity = 0 -> Null metrics
                $availability = null;
                $performance = null;
                $quality = null;
                $oee = null;
            }

            // Increment totals for raw weighted calculations (returned in summary for display)
            $totalWorkHours += $dayWorkHours;
            $totalDowntimeHours += $dayDowntimeHours;
            $totalActualQty += $dayActualQty;
            $totalTargetQty += $dayTargetQty;
            $totalRejectQty += $dayRejectQty;

            // Accumulate working day capped parameters into monthly OEE summary parameters (excluding capacity = 0 days)
            if ($dayPlannedCapacity > 0.0) {
                $globalCappedRuntime += $dayCappedRuntime;
                $globalCappedActualQty += $dayCappedActualQty;
                $globalCappedTargetQty += $dayCappedTargetQty;
                $globalCappedGoodOutput += $dayCappedGoodOutput;
                $globalPlannedCapacity += $dayPlannedCapacity;
                $globalWorkHoursForSummary += $dayWorkHours;
                $globalActualQtyForSummary += $dayActualQty;
            }

            $rows[] = [
                'date' => $date,
                'planned_capacity' => $dayPlannedCapacity,
                'work_hours' => $dayWorkHours,
                'downtime_hours' => $dayDowntimeHours,
                'actual_qty' => $dayActualQty,
                'target_qty' => $dayTargetQty,
                'reject_qty' => $dayRejectQty,
                'availability' => $availability,
                'performance' => $performance,
                'quality' => $quality,
                'oee' => $oee,
            ];
        }

        // Sort ascending by date (ensured at SQL level, but safe to keep consistent)
        usort($rows, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        // 3. Compute Weighted Summary Recalculation (excluding capacity = 0 days)
        $summaryAvailability = $globalPlannedCapacity > 0.0 ? min(1.0, max(0.0, $globalCappedRuntime / $globalPlannedCapacity)) : null;
        $summaryPerformance = $globalCappedTargetQty > 0 ? min(1.0, max(0.0, $globalCappedActualQty / $globalCappedTargetQty)) : null;
        $summaryQuality = $globalActualQtyForSummary > 0 ? min(1.0, max(0.0, $globalCappedGoodOutput / $globalActualQtyForSummary)) : null;

        if ($summaryAvailability !== null && $summaryPerformance !== null && $summaryQuality !== null) {
            $summaryOee = min(1.0, max(0.0, $summaryAvailability * $summaryPerformance * $summaryQuality));
        } else {
            $summaryOee = null;
        }

        $summary = [
            'planned_capacity' => $globalPlannedCapacity,
            'runtime' => $globalCappedRuntime,
            'work_hours' => $totalWorkHours,
            'downtime_hours' => $totalDowntimeHours,
            'actual_qty' => $totalActualQty,
            'target_qty' => $totalTargetQty,
            'reject_qty' => $totalRejectQty,
            'availability' => $summaryAvailability,
            'performance' => $summaryPerformance,
            'quality' => $summaryQuality,
            'oee' => $summaryOee,
        ];

        // 4. Fetch Actionable Insight Payloads inside the service contract
        $topDowntimeReasons = $this->getTopDowntimeReasons($startDate, $endDate, $departmentCode, $machineCode);
        $topRejectReasons = $this->getTopRejectReasons($startDate, $endDate, $departmentCode, $machineCode);

        return [
            'rows' => $rows,
            'summary' => $summary,
            'row_count' => count($rows),
            'top_downtime_reasons' => $topDowntimeReasons,
            'top_reject_reasons' => $topRejectReasons,
        ];
    }

    /**
     * Helper: Pulls Top 5 Downtime Reasons using direct SQL sums and COALESCE logic
     */
    protected function getTopDowntimeReasons(
        string $startDate,
        string $endDate,
        string $departmentCode,
        ?string $machineCode = null
    ): array {
        // intentional: department isolation manually enforced below
        $query = DowntimeLog::withoutGlobalScopes()
            ->where('entry_type', 'downtime')
            ->whereBetween('downtime_date', [$startDate, $endDate])
            ->where('department_code', $departmentCode)
            ->whereNotNull('machine_code')
            ->where('machine_code', '!=', '');

        if ($machineCode !== null) {
            $query->where(DB::raw('LOWER(machine_code)'), $this->normalizeMachineCode($machineCode));
        }

        // denominator = total downtime ALL reasons in the period
        $totalDowntimeMinutes = (float) DowntimeLog::withoutGlobalScopes() // intentional: department isolation manually enforced below
            ->where('entry_type', 'downtime')
            ->whereBetween('downtime_date', [$startDate, $endDate])
            ->where('department_code', $departmentCode)
            ->whereNotNull('machine_code')
            ->where('machine_code', '!=', '')
            ->when($machineCode !== null, function ($q) use ($machineCode) {
                $q->where(DB::raw('LOWER(machine_code)'), $this->normalizeMachineCode($machineCode));
            })
            ->sum('duration_minutes');

        $rows = $query->select(
                DB::raw("COALESCE(NULLIF(reason, ''), 'Tidak Ada Keterangan') as reason"),
                DB::raw('SUM(duration_minutes) as total_duration_minutes'),
                DB::raw('COUNT(*) as occurrences_count') // Task 7: occurrences count
            )
            ->groupBy(DB::raw("COALESCE(NULLIF(reason, ''), 'Tidak Ada Keterangan')"))
            ->orderBy(DB::raw('SUM(duration_minutes)'), 'desc')
            ->limit(5)
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $hours = $row->total_duration_minutes / 60;
            // Task 1: Contribution strictly scoped by denominator
            $contribution = $totalDowntimeMinutes > 0.0 
                ? ($row->total_duration_minutes / $totalDowntimeMinutes) * 100 
                : 0.0;

            $result[] = [
                'reason' => $row->reason,
                'hours' => $hours,
                'contribution' => round($contribution, 2),
                'count' => (int) $row->occurrences_count,
            ];
        }

        return $result;
    }

    /**
     * Helper: Pulls Top 5 Reject Reasons using direct SQL sums and COALESCE logic
     */
    protected function getTopRejectReasons(
        string $startDate,
        string $endDate,
        string $departmentCode,
        ?string $machineCode = null
    ): array {
        $query = RejectLog::withoutGlobalScopes() // intentional: department isolation manually enforced below
            ->whereBetween('reject_date', [$startDate, $endDate])
            ->where('department_code', $departmentCode)
            ->whereNotNull('machine_code')
            ->where('machine_code', '!=', '');

        if ($machineCode !== null) {
            $query->where(DB::raw('LOWER(machine_code)'), $this->normalizeMachineCode($machineCode));
        }

        $rows = $query->select(
                DB::raw("COALESCE(NULLIF(reject_reason, ''), 'Tidak Ada Keterangan') as reject_reason"),
                DB::raw('SUM(reject_qty) as total_reject_qty')
            )
            ->groupBy(DB::raw("COALESCE(NULLIF(reject_reason, ''), 'Tidak Ada Keterangan')"))
            ->orderBy(DB::raw('SUM(reject_qty)'), 'desc')
            ->limit(5)
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'reason' => $row->reject_reason,
                'qty' => (int) $row->total_reject_qty,
            ];
        }

        return $result;
    }

    /**
     * Helper: Performs raw production & target SQL aggregates
     */
    protected function aggregateProduction(
        string $startDate,
        string $endDate,
        string $departmentCode,
        ?string $machineCode
    ): array {
        // intentional:
        // daily rows generated strictly from production transaction dates
        // reject-only or downtime-only orphan dates excluded intentionally
        $query = ProductionLog::withoutGlobalScopes() // intentional: department isolation manually enforced below
            ->whereBetween('production_date', [$startDate, $endDate])
            ->where('department_code', $departmentCode)
            ->whereNotNull('machine_code')
            ->where('machine_code', '!=', '');

        if ($machineCode !== null) {
            $query->where(DB::raw('LOWER(machine_code)'), $this->normalizeMachineCode($machineCode));
        }

        $rows = $query->select(
                'production_date',
                'machine_code',
                DB::raw('SUM(work_hours) as total_work_hours'),
                DB::raw('SUM(target_qty) as total_target_qty'),
                DB::raw('SUM(actual_qty) as total_actual_qty')
            )
            ->groupBy('production_date', 'machine_code')
            ->orderBy('production_date', 'asc')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $mCode = $this->normalizeMachineCode($row->machine_code);
            $result[$row->production_date][$mCode] = [
                'work_hours' => (float) $row->total_work_hours,
                'target_qty' => (int) $row->total_target_qty,
                'actual_qty' => (int) $row->total_actual_qty,
            ];
        }

        return $result;
    }

    /**
     * Helper: Performs raw reject SQL aggregates
     */
    protected function aggregateRejects(
        string $startDate,
        string $endDate,
        string $departmentCode,
        ?string $machineCode
    ): array {
        $query = RejectLog::withoutGlobalScopes() // intentional: department isolation manually enforced below
            ->whereBetween('reject_date', [$startDate, $endDate])
            ->where('department_code', $departmentCode)
            ->whereNotNull('machine_code')
            ->where('machine_code', '!=', '');

        if ($machineCode !== null) {
            $query->where(DB::raw('LOWER(machine_code)'), $this->normalizeMachineCode($machineCode));
        }

        $rows = $query->select(
                'reject_date',
                'machine_code',
                DB::raw('SUM(reject_qty) as total_reject_qty')
            )
            ->groupBy('reject_date', 'machine_code')
            ->orderBy('reject_date', 'asc')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $mCode = $this->normalizeMachineCode($row->machine_code);
            $result[$row->reject_date][$mCode] = [
                'reject_qty' => (int) $row->total_reject_qty,
            ];
        }

        return $result;
    }

    /**
     * Helper: Performs raw downtime minutes SQL aggregates
     */
    protected function aggregateDowntime(
        string $startDate,
        string $endDate,
        string $departmentCode,
        ?string $machineCode
    ): array {
        $query = DowntimeLog::withoutGlobalScopes() // intentional: department isolation manually enforced below
            ->where('entry_type', 'downtime')
            ->whereBetween('downtime_date', [$startDate, $endDate])
            ->where('department_code', $departmentCode)
            ->whereNotNull('machine_code')
            ->where('machine_code', '!=', '');

        if ($machineCode !== null) {
            $query->where(DB::raw('LOWER(machine_code)'), $this->normalizeMachineCode($machineCode));
        }

        $rows = $query->select(
                'downtime_date',
                'machine_code',
                DB::raw('SUM(duration_minutes) as total_duration_minutes')
            )
            ->groupBy('downtime_date', 'machine_code')
            ->orderBy('downtime_date', 'asc')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $mCode = $this->normalizeMachineCode($row->machine_code);
            $result[$row->downtime_date][$mCode] = [
                'downtime_hours' => $row->total_duration_minutes / 60,
            ];
        }

        return $result;
    }

    /**
     * Helper: Executes mathematical OEE formulas with strict safeguards
     */
    protected function calculateOeeFactors(
        float $workHours,
        float $downtimeHours,
        int $actualQty,
        int $targetQty,
        int $rejectQty
    ): array {
        // 1. Availability (A)
        $runtime = max(0.0, $workHours - $downtimeHours);
        $availability = $workHours > 0.0
            ? $runtime / $workHours
            : 0.0;
        $availability = min(1.0, max(0.0, $availability)); // Defensive Hardening

        // 2. Performance (P)
        $performance = $targetQty > 0
            ? min(1.0, $actualQty / $targetQty) // capped intentionally to prevent OEE inflation
            : 0.0;
        $performance = min(1.0, max(0.0, $performance)); // Defensive Hardening

        // 3. Quality (Q)
        $goodOutput = max(0, $actualQty - $rejectQty);
        $quality = $actualQty > 0
            ? $goodOutput / $actualQty
            : 0.0;
        $quality = min(1.0, max(0.0, $quality)); // Defensive Hardening

        // 4. OEE
        $oee = $availability * $performance * $quality;
        $oee = min(1.0, max(0.0, $oee)); // Defensive Hardening

        return [
            'availability' => $availability,
            'performance' => $performance,
            'quality' => $quality,
            'oee' => $oee,
        ];
    }

    /**
     * Helper: Normalize machine code to lowercase
     */
    protected function normalizeMachineCode(?string $code): ?string
    {
        return $code !== null ? strtolower(trim($code)) : null;
    }

    /**
     * Helper: Retrieves planned capacity overrides for the given date range and department
     */
    protected function getPlannedCapacities(string $startDate, string $endDate, string $departmentCode)
    {
        return \App\Models\PlannedCapacity::withoutGlobalScopes()
            ->whereBetween('work_date', [$startDate, $endDate])
            ->where('department_code', $departmentCode)
            ->get();
    }
}
