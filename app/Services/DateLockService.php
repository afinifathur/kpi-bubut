<?php

namespace App\Services;

use App\Models\DailyLock;
use Carbon\Carbon;

class DateLockService
{
    /**
     * Determine if a date is locked.
     * Locked if:
     * 1. Manual lock exists and is_locked = true
     * 2. Manual lock does NOT exist, but date is older than 7 days (Auto-lock)
     *
     * @param string $date Y-m-d
     * @return bool
     */
    public static function isLocked($date)
    {
        $lock = DailyLock::where('date', $date)->first();

        if ($lock) {
            return $lock->is_locked;
        }

        // Auto-lock logic: Lock if older than 7 days
        // Today is Day 0.
        $kpiDate = Carbon::parse($date);
        $threshold = Carbon::now()->subDays(7);

        // Example: If today is 28th. Sub 7 days = 21st.
        // If date is 20th... 20 < 21 ? Yes. Auto-locked.
        // If date is 22nd... 22 < 21 ? No. Open.

        return $kpiDate->lt($threshold);
    }
}
