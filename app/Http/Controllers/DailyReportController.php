<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionLog;
use App\Models\MdOperatorMirror;
use Barryvdh\DomPDF\Facade\Pdf;

class DailyReportController extends Controller
{
    /**
     * ===============================
     * INDEX (LIST TANGGAL)
     * ===============================
     */
    /**
     * ===============================
     * TOGGLE LOCK (MR/DIREKTUR ONLY)
     * ===============================
     */
    public function toggleLock(Request $request)
    {
        $user = auth()->user();
        if (!in_array($user->role, ['direktur', 'mr'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $date = $request->input('date');
        $lock = \App\Models\DailyLock::where('date', $date)->first();

        if ($lock) {
            // Toggle existing
            $lock->is_locked = !$lock->is_locked;
            $lock->unlocked_by = $user->id;
            $lock->save();
        } else {
            // Create new override
            // If current state (without record) is LOCKED (old date), we want to UNLOCK (false).
            // If current state is OPEN (new date), we want to LOCK (true).
            $isCurrentlyLocked = \App\Services\DateLockService::isLocked($date);

            \App\Models\DailyLock::create([
                'date' => $date,
                'is_locked' => !$isCurrentlyLocked, // Invert current state
                'unlocked_by' => $user->id
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * ===============================
     * INDEX (LIST TANGGAL)
     * ===============================
     */
    public function operatorIndex()
    {
        // Ambil summary per tanggal
        $dates = ProductionLog::selectRaw('
                production_date, 
                SUM(actual_qty) as total_qty, 
                SUM(target_qty) as total_target, 
                AVG(achievement_percent) as avg_kpi,
                COUNT(*) as total_logs
            ')
            ->groupBy('production_date')
            ->orderBy('production_date', 'desc')
            ->get();

        // Calculate lock status for each date
        $dates->transform(function ($item) {
            $item->is_locked = \App\Services\DateLockService::isLocked($item->production_date);
            return $item;
        });

        return view('daily_report.operator.index', [
            'dates' => $dates,
        ]);
    }

    /**
     * ===============================
     * SHOW (DETAIL HARIAN)
     * ===============================
     */
    public function operatorShow(Request $request, $date)
    {
        // Check Lock
        $isLocked = \App\Services\DateLockService::isLocked($date);

        // Sorting parameters
        $sort = $request->get('sort', 'default');
        $direction = $request->get('direction', 'asc');

        // Validate direction
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        // Map sort column names to database fields
        $sortColumns = [
            'shift' => 'shift',
            'operator' => 'operator_code',
            'machine' => 'machine_code',
            'work_hours' => 'work_hours',
            'target' => 'target_qty',
            'actual' => 'actual_qty',
            'kpi' => 'achievement_percent',
        ];

        // Build query
        $query = ProductionLog::with(['operator', 'machine', 'item'])
            ->where('production_date', $date);

        // Apply sorting
        if ($sort !== 'default' && isset($sortColumns[$sort])) {
            $query->orderBy($sortColumns[$sort], $direction);
        } else {
            // Default sorting (shift → operator → time)
            $query->orderBy('shift')
                ->orderBy('operator_code')
                ->orderBy('time_start');
        }

        $rows = $query->get();

        return view('daily_report.operator.show', [
            'rows' => $rows,
            'date' => $date,
            'isLocked' => $isLocked,
            'currentSort' => $sort,
            'currentDirection' => $direction,
        ]);
    }

    /**
     * ===============================
     * DESTROY (HAPUS INPUTAN)
     * ===============================
     */
    public function operatorDestroy($id)
    {
        if (auth()->user()->isReadOnly()) {
            abort(403, 'Unauthorized action.');
        }

        $log = ProductionLog::findOrFail($id);

        if (\App\Services\DateLockService::isLocked($log->production_date)) {
            abort(403, 'Date is locked. Cannot delete data.');
        }

        // Simpan info untuk flash message
        $info = "Inputan Operator {$log->operator_code} di Mesin {$log->machine_code}";
        $date = $log->production_date; // Capture date before delete

        $log->delete();

        // Regenerate KPI (Sync Dashboard)
        \App\Services\DailyKpiService::generateOperatorDaily($date);
        \App\Services\DailyKpiService::generateMachineDaily($date);

        return redirect()
            ->back()
            ->with('success', "Data berhasil dihapus: $info");
    }

    /**
     * ===============================
     * EXPORT PDF (PORTRAIT)
     * ===============================
     */
    public function operatorExportPdf(Request $request, $date)
    {
        // Sorting parameters (same as web view)
        $sort = $request->get('sort', 'default');
        $direction = $request->get('direction', 'asc');

        // Validate direction
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        // Map sort column names to database fields
        $sortColumns = [
            'shift' => 'shift',
            'operator' => 'operator_code',
            'machine' => 'machine_code',
            'work_hours' => 'work_hours',
            'target' => 'target_qty',
            'actual' => 'actual_qty',
            'kpi' => 'achievement_percent',
        ];

        // Build query
        $query = ProductionLog::with(['operator', 'machine', 'item'])
            ->where('production_date', $date);

        // Apply sorting
        if ($sort !== 'default' && isset($sortColumns[$sort])) {
            $query->orderBy($sortColumns[$sort], $direction);
        } else {
            // Default sorting (shift → operator → time)
            $query->orderBy('shift')
                ->orderBy('operator_code')
                ->orderBy('time_start');
        }

        $rows = $query->get();

        // Calculate shift summaries (Shift 1, 2, 3)
        $shiftSummary = [];
        for ($shift = 1; $shift <= 3; $shift++) {
            $shiftData = $rows->where('shift', $shift);
            $totalActual = $shiftData->sum('actual_qty');
            $totalTarget = $shiftData->sum('target_qty');

            $shiftSummary[$shift] = [
                'actual' => $totalActual,
                'target' => $totalTarget,
                'percentage' => $totalTarget > 0
                    ? round(($totalActual / $totalTarget) * 100, 1)
                    : 0,
                'count' => $shiftData->count(),
            ];
        }

        // Calculate daily total (all shifts combined)
        $dailyTotal = [
            'actual' => $rows->sum('actual_qty'),
            'target' => $rows->sum('target_qty'),
            'percentage' => $rows->sum('target_qty') > 0
                ? round(($rows->sum('actual_qty') / $rows->sum('target_qty')) * 100, 1)
                : 0,
        ];

        // Calculate remark breakdown (keterangan)
        $remarkBreakdown = $rows->groupBy('remark')->map(function ($group, $remarkKey) {
            return [
                'label' => empty($remarkKey) ? 'Normal (Selesai)' : $remarkKey,
                'qty' => $group->sum('actual_qty'),
                'count' => $group->count(),
            ];
        })->sortByDesc('qty')->values();

        $pdf = Pdf::loadView('daily_report.operator.pdf', [
            'rows' => $rows,
            'date' => $date,
            'shiftSummary' => $shiftSummary,
            'dailyTotal' => $dailyTotal,
            'remarkBreakdown' => $remarkBreakdown,
        ]);

        // Portrait orientation as requested
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("Laporan-Harian-Operator-{$date}.pdf");
    }

    /**
     * ===============================
     * DOWNTIME REPORT SECTION
     * ===============================
     */

    /**
     * INDEX (LIST TANGGAL DOWNTIME)
     */
    public function downtimeIndex()
    {
        $dates = \App\Models\DowntimeLog::selectRaw('
                downtime_date, 
                SUM(duration_minutes) as total_minutes, 
                COUNT(*) as total_logs
            ')
            ->groupBy('downtime_date')
            ->orderBy('downtime_date', 'desc')
            ->get();

        // Calculate lock status
        $dates->transform(function ($item) {
            $item->is_locked = \App\Services\DateLockService::isLocked($item->downtime_date);
            return $item;
        });

        return view('daily_report.downtime.index', [
            'dates' => $dates,
        ]);
    }

    /**
     * SHOW (DETAIL HARIAN DOWNTIME)
     */
    public function downtimeShow($date)
    {
        $isLocked = \App\Services\DateLockService::isLocked($date);

        $rows = \App\Models\DowntimeLog::with(['machine', 'operator'])
            ->where('downtime_date', $date)
            ->orderBy('machine_code')
            ->get();

        return view('daily_report.downtime.show', [
            'rows' => $rows,
            'date' => $date,
            'isLocked' => $isLocked
        ]);
    }

    /**
     * DESTROY (HAPUS DATA DOWNTIME)
     */
    public function downtimeDestroy($id)
    {
        if (auth()->user()->isReadOnly()) {
            abort(403, 'Unauthorized action.');
        }

        $log = \App\Models\DowntimeLog::findOrFail($id);

        if (\App\Services\DateLockService::isLocked($log->downtime_date)) {
            abort(403, 'Date is locked. Cannot delete data.');
        }

        $info = "Downtime Mesin {$log->machine_code} ({$log->duration_minutes} min)";
        $log->delete();

        return redirect()
            ->back()
            ->with('success', "Data berhasil dihapus: $info");
    }

    /**
     * EXPORT PDF (DOWNTIME)
     */
    public function downtimeExportPdf($date)
    {
        $rows = \App\Models\DowntimeLog::with(['machine', 'operator'])
            ->where('downtime_date', $date)
            ->orderBy('machine_code')
            ->get();

        $pdf = Pdf::loadView('daily_report.downtime.pdf', [
            'rows' => $rows,
            'date' => $date,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("Laporan-Harian-Downtime-{$date}.pdf");
    }
}
