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
        // 1. Run direct SQL aggregates
        $productionData = $this->aggregateProduction($startDate, $endDate, $departmentCode, $machineCode);
        $rejectData = $this->aggregateRejects($startDate, $endDate, $departmentCode, $machineCode);
        $downtimeData = $this->aggregateDowntime($startDate, $endDate, $departmentCode, $machineCode);

        // 2. Stitch data by date keys present in productionData
        // (Rule 11: Empty Day Rule - Days without production logs DO NOT generate rows)
        $rows = [];
        $totalWorkHours = 0.0;
        $totalDowntimeHours = 0.0;
        $totalActualQty = 0;
        $totalTargetQty = 0;
        $totalRejectQty = 0;

        foreach ($productionData as $date => $prod) {
            $workHours = $prod['work_hours'];
            $targetQty = $prod['target_qty'];
            $actualQty = $prod['actual_qty'];

            $rejectQty = isset($rejectData[$date]) ? $rejectData[$date]['reject_qty'] : 0;
            $downtimeHours = isset($downtimeData[$date]) ? $downtimeData[$date]['downtime_hours'] : 0.0;

            // Increment totals for weighted calculations
            $totalWorkHours += $workHours;
            $totalDowntimeHours += $downtimeHours;
            $totalActualQty += $actualQty;
            $totalTargetQty += $targetQty;
            $totalRejectQty += $rejectQty;

            // Calculate factors
            $factors = $this->calculateOeeFactors($workHours, $downtimeHours, $actualQty, $targetQty, $rejectQty);

            $rows[] = [
                'date' => $date,
                'work_hours' => $workHours,
                'downtime_hours' => $downtimeHours,
                'actual_qty' => $actualQty,
                'target_qty' => $targetQty,
                'reject_qty' => $rejectQty,
                'availability' => $factors['availability'],
                'performance' => $factors['performance'],
                'quality' => $factors['quality'],
                'oee' => $factors['oee'],
            ];
        }

        // Sort ascending by date (ensured at SQL level, but safe to keep consistent)
        usort($rows, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        // 3. Compute Weighted Summary Recalculation
        $summaryFactors = $this->calculateOeeFactors(
            $totalWorkHours,
            $totalDowntimeHours,
            $totalActualQty,
            $totalTargetQty,
            $totalRejectQty
        );

        $summary = [
            'work_hours' => $totalWorkHours,
            'downtime_hours' => $totalDowntimeHours,
            'actual_qty' => $totalActualQty,
            'target_qty' => $totalTargetQty,
            'reject_qty' => $totalRejectQty,
            'availability' => $summaryFactors['availability'],
            'performance' => $summaryFactors['performance'],
            'quality' => $summaryFactors['quality'],
            'oee' => $summaryFactors['oee'],
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
            // TODO:
            // Normalize machine_code storage to lowercase
            // to avoid runtime LOWER() index bypass
            $query->where(DB::raw('LOWER(machine_code)'), $this->normalizeMachineCode($machineCode));
        }

        $rows = $query->select(
                'production_date',
                DB::raw('SUM(work_hours) as total_work_hours'),
                DB::raw('SUM(target_qty) as total_target_qty'),
                DB::raw('SUM(actual_qty) as total_actual_qty')
            )
            // governance:
            // aggregated by date only to avoid machine x date row explosion
            ->groupBy('production_date')
            ->orderBy('production_date', 'asc')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->production_date] = [
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
            // TODO:
            // Normalize machine_code storage to lowercase
            // to avoid runtime LOWER() index bypass
            $query->where(DB::raw('LOWER(machine_code)'), $this->normalizeMachineCode($machineCode));
        }

        $rows = $query->select(
                'reject_date',
                DB::raw('SUM(reject_qty) as total_reject_qty')
            )
            // Governance:
            // 1 day = 1 summarized row
            // even in ALL MACHINE aggregation mode
            ->groupBy('reject_date')
            ->orderBy('reject_date', 'asc')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->reject_date] = [
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
            // TODO:
            // Normalize machine_code storage to lowercase
            // to avoid runtime LOWER() index bypass
            $query->where(DB::raw('LOWER(machine_code)'), $this->normalizeMachineCode($machineCode));
        }

        $rows = $query->select(
                'downtime_date',
                DB::raw('SUM(duration_minutes) as total_duration_minutes')
            )
            // Governance:
            // 1 day = 1 summarized row
            // even in ALL MACHINE aggregation mode
            ->groupBy('downtime_date')
            ->orderBy('downtime_date', 'asc')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->downtime_date] = [
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
}
