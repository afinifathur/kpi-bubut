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
        $date = $request->get('date', now()->toDateString());

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

            // 2. Regenerate KPI for the selected date (Ini yang utama)
            DailyKpiService::generateOperatorDaily($date);
            DailyKpiService::generateMachineDaily($date);

            return back()->with('success', 'KPI telah diperbarui untuk tanggal ' . $date . '. (Sinkronisasi master data mungkin tidak sempurna jika ada error di master)');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal melakukan sinkronisasi: ' . $e->getMessage());
        }
    }
}
