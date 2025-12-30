<?php

namespace App\Http\Controllers;

use App\Models\MdMachineMirror;

class DashboardController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | MACHINE STATUS (ACTIVE ONLY)
        |--------------------------------------------------------------------------
        */
        $machines = MdMachineMirror::where('status', 'active')
            ->orderBy('department_code')
            ->orderBy('code')
            ->get();

        $machineSummary = [
            'ONLINE' => MdMachineMirror::where('status', 'active')
                ->where('runtime_status', 'ONLINE')
                ->count(),

            'STALE' => MdMachineMirror::where('status', 'active')
                ->where('runtime_status', 'STALE')
                ->count(),

            'OFFLINE' => MdMachineMirror::where('status', 'active')
                ->where('runtime_status', 'OFFLINE')
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | LEGACY DASHBOARD DATA
        |--------------------------------------------------------------------------
        */
        $legacyData = [
            // tetap seperti sebelumnya
        ];

        return view('dashboard.index', compact(
            'machines',
            'machineSummary',
            'legacyData'
        ));
    }
}
