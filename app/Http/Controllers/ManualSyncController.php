<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Services\DailyKpiService;
use Carbon\Carbon;

class ManualSyncController extends Controller
{
    /**
     * Trigger manual sync and KPI regeneration
     */
    public function sync(Request $request)
    {
        // Handle Department Context Switch for Manager/Director
        if ($request->has('department_context')) {
            $context = $request->get('department_context');
            if ($context === 'ALL') {
                session()->forget('selected_department_code');
            } else {
                session(['selected_department_code' => $context]);
            }
            return back()->with('info', 'Switched to department: ' . $context);
        }

        $date = $request->get('date', now()->toDateString());
        $startDate = $request->get('start_date', $date);
        $endDate = $request->get('end_date', $date);

        try {
            // 1. Pull Master Data (Bersifat opsional, jika gagal tetap lanjut ke KPI)
            try {
                Artisan::call('pull:master-items');
                Artisan::call('pull:master-operators');
                Artisan::call('pull:master-machines');
                Artisan::call('pull:master-heat-numbers');
            } catch (\Exception $e) {
                // Log error tapi jangan hentikan proses KPI
                \Log::error("Sync Master Data failed: " . $e->getMessage());
            }

            // 2. Regenerate KPI for the range (or single date)
            $current = \Carbon\Carbon::parse($startDate);
            $last = \Carbon\Carbon::parse($endDate);

            while ($current->lte($last)) {
                DailyKpiService::generateOperatorDaily($current->toDateString());
                DailyKpiService::generateMachineDaily($current->toDateString());
                $current->addDay();
            }

            $dateLabel = ($startDate === $endDate) ? $startDate : "$startDate s/d $endDate";
            return back()->with('success', 'KPI telah diperbarui untuk ' . $dateLabel);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal melakukan sinkronisasi: ' . $e->getMessage());
        }
    }
}
