<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionLog;
use App\Models\RejectLog;
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
            'cycle_time' => 'cycle_time_used_sec',
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
     * EDIT (FORM EDIT INPUTAN)
     * ===============================
     */
    public function operatorEdit($id)
    {
        if (auth()->user()->isReadOnly()) {
            abort(403, 'Unauthorized action.');
        }

        $log = ProductionLog::with(['operator', 'machine', 'item'])->findOrFail($id);

        if (\App\Services\DateLockService::isLocked($log->production_date)) {
            abort(403, 'Data sudah dikunci. Tidak dapat mengedit.');
        }

        return view('daily_report.operator.edit', [
            'log' => $log,
        ]);
    }

    /**
     * ===============================
     * UPDATE (SIMPAN EDIT INPUTAN)
     * ===============================
     */
    public function operatorUpdate(Request $request, $id)
    {
        if (auth()->user()->isReadOnly()) {
            abort(403, 'Unauthorized action.');
        }

        $log = ProductionLog::findOrFail($id);

        if (\App\Services\DateLockService::isLocked($log->production_date)) {
            abort(403, 'Data sudah dikunci. Tidak dapat mengedit.');
        }

        $validated = $request->validate([
            'shift' => 'required|string|max:10',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i',
            'cycle_time_minutes' => 'required|integer|min:0',
            'cycle_time_seconds' => 'required|integer|min:0|max:59',
            'actual_qty' => 'required|integer|min:0',
            'remark' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:255',
        ]);

        // Re-calculate work hours
        $startSeconds = strtotime($validated['time_start']);
        $endSeconds = strtotime($validated['time_end']);

        if ($endSeconds < $startSeconds) {
            $endSeconds += 86400;
        }

        $workSeconds = $endSeconds - $startSeconds;

        if ($workSeconds <= 0) {
            return back()->withErrors(['time_end' => 'Jam selesai harus lebih besar dari jam mulai.'])->withInput();
        }

        $workHours = round($workSeconds / 3600, 2);

        // Re-calculate cycle time
        $cycleTimeSec = ($validated['cycle_time_minutes'] * 60) + $validated['cycle_time_seconds'];

        if ($cycleTimeSec <= 0) {
            return back()->withErrors(['cycle_time_seconds' => 'Total Cycle Time tidak boleh 0 detik.'])->withInput();
        }

        // Re-calculate target & achievement
        $targetQty = intdiv($workSeconds, $cycleTimeSec);
        $actualQty = (int) $validated['actual_qty'];
        $achievementPercent = $targetQty > 0
            ? round(($actualQty / $targetQty) * 100, 2)
            : 0;

        // Update record
        $log->update([
            'shift' => $validated['shift'],
            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'work_hours' => $workHours,
            'cycle_time_used_sec' => $cycleTimeSec,
            'target_qty' => $targetQty,
            'actual_qty' => $actualQty,
            'achievement_percent' => $achievementPercent,
            'remark' => $validated['remark'] ?? null,
            'note' => $validated['note'] ?? null,
        ]);

        // Regenerate KPI
        \App\Services\DailyKpiService::generateOperatorDaily($log->production_date);
        \App\Services\DailyKpiService::generateMachineDaily($log->production_date);

        return redirect()
            ->route('daily_report.operator.show', $log->production_date)
            ->with('success', "Data berhasil diperbarui: Operator {$log->operator_code} di Mesin {$log->machine_code}");
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

        return $pdf->stream("Laporan-Harian-Downtime-{$date}.pdf");
    }

    /**
     * EXPORT EXCEL (DOWNTIME)
     */
    public function downtimeExportExcel($date)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DowntimeExport($date), 
            "Laporan-Harian-Downtime-{$date}.xlsx"
        );
    }

    /**
     * EDIT (FORM EDIT DOWNTIME/CHECK)
     */
    public function downtimeEdit($id)
    {
        if (auth()->user()->isReadOnly()) {
            abort(403, 'Unauthorized action.');
        }

        $log = \App\Models\DowntimeLog::with(['machine', 'operator'])->findOrFail($id);

        if (\App\Services\DateLockService::isLocked($log->downtime_date)) {
            abort(403, 'Data sudah dikunci. Tidak dapat mengedit.');
        }

        return view('daily_report.downtime.edit', [
            'log' => $log,
        ]);
    }

    /**
     * UPDATE (SIMPAN EDIT DOWNTIME/CHECK)
     */
    public function downtimeUpdate(Request $request, $id)
    {
        if (auth()->user()->isReadOnly()) {
            abort(403, 'Unauthorized action.');
        }

        $log = \App\Models\DowntimeLog::findOrFail($id);

        if (\App\Services\DateLockService::isLocked($log->downtime_date)) {
            abort(403, 'Data sudah dikunci. Tidak dapat mengedit.');
        }

        $entryType = $log->entry_type;

        $rules = [
            'note' => 'nullable|string|max:255',
        ];

        if ($entryType === 'downtime') {
            $rules += [
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
                'reason' => 'required|string|max:255',
            ];
        } else {
            $rules += [
                'size_category' => 'required|string',
                'rpm_feeding_mode' => 'required|in:kasar,finish',
                'check_cekam' => 'required|in:Ya,Tidak',
                'check_air_ozo' => 'required|in:Ya,Tidak',
                'check_eretan' => 'required|in:Ya,Tidak',
                'check_pisau' => 'required|in:Ya,Tidak',
                'check_kebersihan' => 'required|in:Ya,Tidak',
                'check_oli' => 'required|in:Ya,Tidak',
                'rpm_value' => 'required|integer',
                'rpm_id_value' => 'required|integer',
                'feeding_value' => 'required|numeric',
                'feeding_id_value' => 'required|numeric',
            ];
        }

        $validated = $request->validate($rules);

        if ($entryType === 'downtime') {
            $start = strtotime($validated['start_time']);
            $end = strtotime($validated['end_time']);
            $durationMinutes = (int) round(($end - $start) / 60);

            $log->update([
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'duration_minutes' => $durationMinutes,
                'reason' => $validated['reason'],
                'note' => $validated['note'] ?? null,
            ]);
        } else {
            $log->update([
                'size_category' => $validated['size_category'],
                'rpm_feeding_mode' => $validated['rpm_feeding_mode'],
                'check_cekam' => $validated['check_cekam'],
                'check_air_ozo' => $validated['check_air_ozo'],
                'check_eretan' => $validated['check_eretan'],
                'check_pisau' => $validated['check_pisau'],
                'check_kebersihan' => $validated['check_kebersihan'],
                'check_oli' => $validated['check_oli'],
                'rpm_value' => $validated['rpm_value'],
                'rpm_id_value' => $validated['rpm_id_value'],
                'feeding_value' => $validated['feeding_value'],
                'feeding_id_value' => $validated['feeding_id_value'],
                'note' => $validated['note'] ?? null,
            ]);
        }

        return redirect()
            ->route('daily_report.downtime.show', $log->downtime_date)
            ->with('success', "Data " . ($entryType === 'check' ? 'Pengecekan' : 'Downtime') . " berhasil diperbarui.");
    }

    /**
     * ===============================
     * REJECT REPORT SECTION
     * ===============================
     */

    /**
     * INDEX (LIST TANGGAL REJECT)
     */
    public function rejectIndex()
    {
        $dates = RejectLog::selectRaw('
                reject_date, 
                SUM(reject_qty) as total_qty, 
                COUNT(*) as total_logs
            ')
            ->groupBy('reject_date')
            ->orderBy('reject_date', 'desc')
            ->get();

        // Calculate lock status
        $dates->transform(function ($item) {
            $item->is_locked = \App\Services\DateLockService::isLocked($item->reject_date);
            return $item;
        });

        return view('daily_report.reject.index', [
            'dates' => $dates,
        ]);
    }

    /**
     * SHOW (DETAIL HARIAN REJECT)
     */
    public function rejectShow($date)
    {
        $isLocked = \App\Services\DateLockService::isLocked($date);

        $rows = RejectLog::with(['machine', 'operator', 'item'])
            ->where('reject_date', $date)
            ->orderBy('machine_code')
            ->get();

        return view('daily_report.reject.show', [
            'rows' => $rows,
            'date' => $date,
            'isLocked' => $isLocked
        ]);
    }

    /**
     * DESTROY (HAPUS DATA REJECT)
     */
    public function rejectDestroy($id)
    {
        if (auth()->user()->isReadOnly()) {
            abort(403, 'Unauthorized action.');
        }

        $log = RejectLog::findOrFail($id);

        if (\App\Services\DateLockService::isLocked($log->reject_date)) {
            abort(403, 'Date is locked. Cannot delete data.');
        }

        $info = "Reject Mesin {$log->machine_code} ({$log->reject_qty} pcs)";
        $log->delete();

        return redirect()
            ->back()
            ->with('success', "Data berhasil dihapus: $info");
    }

    /**
     * EXPORT PDF (REJECT)
     */
    public function rejectExportPdf($date)
    {
        $rows = RejectLog::with(['machine', 'operator', 'item'])
            ->where('reject_date', $date)
            ->orderBy('machine_code')
            ->get();

        $pdf = Pdf::loadView('daily_report.reject.pdf', [
            'rows' => $rows,
            'date' => $date,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("Laporan-Harian-Reject-{$date}.pdf");
    }
}
