<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\MdItemMirror;

class KpiBubutService
{
    /**
     * =====================================
     * KPI TANPA DOWNTIME (LEGACY / SIMPLE)
     * =====================================
     */
    public function calculate(
        Collection $productions,
        int $effectiveSeconds
    ): array {
        if ($productions->isEmpty() || $effectiveSeconds <= 0) {
            return [
                'target' => 0,
                'actual' => 0,
                'kpi'    => 0,
            ];
        }

        $actualQty = (int) $productions->sum('actual_qty');

        $cycleTimeSec = $this->getCycleTime(
            $productions->first()->item_code
        );

        // 🔒 FAIL-SAFE
        if ($cycleTimeSec <= 0) {
            return [
                'target' => 0,
                'actual' => $actualQty,
                'kpi'    => 0,
            ];
        }

        $targetQty = intdiv($effectiveSeconds, $cycleTimeSec);

        $kpi = $targetQty > 0
            ? round(($actualQty / $targetQty) * 100, 1)
            : 0;

        return [
            'target' => $targetQty,
            'actual' => $actualQty,
            'kpi'    => $kpi,
        ];
    }

    /**
     * =====================================
     * KPI DENGAN DOWNTIME (MENIT → DETIK)
     * =====================================
     */
    public function calculateWithDowntime(
        Collection $productions,
        Collection $downtimes
    ): array {
        if ($productions->isEmpty()) {
            return [
                'target' => 0,
                'actual' => 0,
                'kpi'    => 0,
            ];
        }

        /**
         * Total waktu produksi (detik)
         */
        $productionSeconds = (int) round(
            $productions->sum('work_hours') * 3600
        );

        /**
         * Total downtime (detik)
         */
        $downtimeSeconds = (int) (
            $downtimes->sum('duration_minutes') * 60
        );

        /**
         * Effective working time
         */
        $effectiveSeconds = max(
            0,
            $productionSeconds - $downtimeSeconds
        );

        $itemCode = $productions->first()->item_code;
        $cycleTimeSec = $this->getCycleTime($itemCode);

        $actualQty = (int) $productions->sum('actual_qty');

        // 🔒 FAIL-SAFE
        if ($cycleTimeSec <= 0 || $effectiveSeconds === 0) {
            return [
                'target' => 0,
                'actual' => $actualQty,
                'kpi'    => 0,
            ];
        }

        $targetQty = intdiv($effectiveSeconds, $cycleTimeSec);

        $kpi = $targetQty > 0
            ? round(($actualQty / $targetQty) * 100, 1)
            : 0;

        return [
            'target' => $targetQty,
            'actual' => $actualQty,
            'kpi'    => $kpi,
        ];
    }

    /**
     * =====================================
     * HELPER: AMBIL CYCLE TIME (ACTIVE ONLY)
     * =====================================
     */
    private function getCycleTime(string $itemCode): int
    {
        return (int) MdItemMirror::where('code', $itemCode)
            ->where('status', 'active') // 🔒 HARD GUARD
            ->value('cycle_time_sec');
    }
}
