<?php

namespace App\Http\Controllers;

use App\Models\MdMachineMirror;

class DashboardController extends Controller
{
    public function index()
    {
        // ===== MACHINE STATUS (BARU) =====
        $machines = MdMachineMirror::orderBy('department_code')
            ->orderBy('code')
            ->get();

        $machineSummary = [
            'ONLINE'  => MdMachineMirror::where('runtime_status', 'ONLINE')->count(),
            'STALE'   => MdMachineMirror::where('runtime_status', 'STALE')->count(),
            'OFFLINE' => MdMachineMirror::where('runtime_status', 'OFFLINE')->count(),
        ];

        // ===== LEGACY DASHBOARD DATA =====
        // Contoh (sesuaikan dengan project Anda)
        $legacyData = [
            // misal:
            // 'today_output' => Production::today()->sum('qty'),
            // 'downtime' => Downtime::today()->count(),
        ];

        return view('dashboard.index', compact(
            'machines',
            'machineSummary',
            'legacyData'
        ));
    }
}
