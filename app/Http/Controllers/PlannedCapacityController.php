<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlannedCapacity;
use App\Models\MdMachineMirror;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PlannedCapacityController extends Controller
{
    /**
     * Render the main dashboard and Handsontable grid.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // 1. Resolve Active Department Context
        $departmentCode = session('selected_department_code');
        if (empty($departmentCode) || $departmentCode === 'all') {
            $departmentCode = $user->department_code;
        }
        
        if (empty($departmentCode)) {
            // Fallback pertahanan tingkat akhir: ambil departemen aktif berawalan 404 dari database
            $firstDept = \Illuminate\Support\Facades\DB::connection('master')
                ->table('md_departments')
                ->where('code', 'LIKE', '404%')
                ->where('status', 'active')
                ->orderBy('code')
                ->first();
            $departmentCode = $firstDept ? $firstDept->code : '404.1';
        }
        
        // 2. Fetch Machines scoped by active department (Temuan #1)
        $machines = MdMachineMirror::where('status', 'active')
            ->where('department_code', $departmentCode)
            ->orderBy('name')
            ->get(['code', 'name']);
            
        // 3. Resolve active filters with case-insensitive normalization (Patch #4)
        $month = $request->get('month', date('Y-m'));
        $machineCode = $request->get('machine_code', 'GLOBAL');
        if (strcasecmp($machineCode, 'GLOBAL') === 0) {
            $machineCode = 'GLOBAL';
        } else {
            $machineCode = strtolower(trim($machineCode));
        }
        
        // 4. Generate Virtual Grid Data (Option B - Exception Only)
        list($year, $monthNumber) = explode('-', $month);
        $daysInMonth = Carbon::create((int)$year, (int)$monthNumber, 1)->daysInMonth;
        
        $startDate = "{$month}-01";
        $endDate = "{$month}-{$daysInMonth}";
        
        // Fetch existing exceptions for this department, month, and machine code
        $exceptions = PlannedCapacity::where('department_code', $departmentCode)
            ->where('machine_code', $machineCode)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->get()
            ->keyBy(function($item) {
                // Defensive coding: handle both string and Carbon objects (Temuan #2)
                return is_string($item->work_date) ? $item->work_date : $item->work_date->format('Y-m-d');
            });
            
        $gridData = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $monthNumber, $day);
            $exception = $exceptions->get($dateStr);
            
            $shift1 = $exception ? (float)$exception->shift_1_hours : 7.0;
            $shift2 = $exception ? (float)$exception->shift_2_hours : 7.0;
            $shift3 = $exception ? (float)$exception->shift_3_hours : 7.0;
            $total = $shift1 + $shift2 + $shift3;
            $status = $exception ? 'Exception' : 'Default';
            
            $gridData[] = [
                'date' => $dateStr,
                'shift_1' => $shift1,
                'shift_2' => $shift2,
                'shift_3' => $shift3,
                'total' => $total,
                'notes' => $exception ? $exception->notes : '',
                'status' => $status
            ];
        }
        
        $summary = $this->calculateSummary($gridData, $machineCode, $machines);

        return view('planned_capacity.index', compact(
            'month', 
            'machineCode', 
            'machines', 
            'gridData', 
            'departmentCode',
            'summary'
        ));
    }

    /**
     * Save/Delete exceptions from Handsontable POST data.
     */
    public function save(Request $request)
    {
        if (auth()->user()->isReadOnly()) {
            return response()->json(['success' => false, 'message' => 'Anda berada dalam mode Read-Only.'], 403);
        }

        // Pre-process grid input: convert empty values to 7.00 before validation (Patch #1)
        $grid = $request->input('grid', []);
        foreach ($grid as $key => $row) {
            if (array_key_exists('shift_1', $row)) {
                $grid[$key]['shift_1'] = ($row['shift_1'] === null || trim((string)$row['shift_1']) === '') ? 7.0 : $row['shift_1'];
            }
            if (array_key_exists('shift_2', $row)) {
                $grid[$key]['shift_2'] = ($row['shift_2'] === null || trim((string)$row['shift_2']) === '') ? 7.0 : $row['shift_2'];
            }
            if (array_key_exists('shift_3', $row)) {
                $grid[$key]['shift_3'] = ($row['shift_3'] === null || trim((string)$row['shift_3']) === '') ? 7.0 : $row['shift_3'];
            }
        }
        $request->merge(['grid' => $grid]);

        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'machine_code' => 'required|string',
            'grid' => 'required|array',
            'grid.*.date' => 'required|date',
            'grid.*.shift_1' => 'required|numeric|min:0|max:24', // Server-side range validation (Temuan #4)
            'grid.*.shift_2' => 'required|numeric|min:0|max:24',
            'grid.*.shift_3' => 'required|numeric|min:0|max:24',
            'grid.*.notes' => 'nullable|string'
        ]);

        $user = Auth::user();
        $departmentCode = session('selected_department_code');
        if (empty($departmentCode) || $departmentCode === 'all') {
            $departmentCode = $user->department_code;
        }

        if (empty($departmentCode)) {
            // Fallback pertahanan tingkat akhir: ambil departemen aktif berawalan 404 dari database
            $firstDept = \Illuminate\Support\Facades\DB::connection('master')
                ->table('md_departments')
                ->where('code', 'LIKE', '404%')
                ->where('status', 'active')
                ->orderBy('code')
                ->first();
            $departmentCode = $firstDept ? $firstDept->code : '404.1';
        }

        // Normalize machine code casing (Patch #4)
        $machineCode = $validated['machine_code'];
        if (strcasecmp($machineCode, 'GLOBAL') === 0) {
            $machineCode = 'GLOBAL';
        } else {
            $machineCode = strtolower(trim($machineCode));
        }

        foreach ($validated['grid'] as $row) {
            $shift1 = (float)($row['shift_1'] ?? 7.0);
            $shift2 = (float)($row['shift_2'] ?? 7.0);
            $shift3 = (float)($row['shift_3'] ?? 7.0);
            $notes = trim($row['notes'] ?? '');

            // SAVE RULE: Jika kapasitas normal (7-7-7) dan notes kosong -> Hapus exception
            if ($shift1 === 7.0 && $shift2 === 7.0 && $shift3 === 7.0 && empty($notes)) {
                PlannedCapacity::where('department_code', $departmentCode)
                    ->where('work_date', $row['date'])
                    ->where('machine_code', $machineCode)
                    ->delete();
            } else {
                // Temuan #3: Gunakan firstOrNew untuk menjaga created_by agar tidak overwrite
                $capacity = PlannedCapacity::firstOrNew([
                    'department_code' => $departmentCode,
                    'work_date' => $row['date'],
                    'machine_code' => $machineCode,
                ]);

                if (!$capacity->exists) {
                    $capacity->created_by = auth()->id();
                }

                $capacity->shift_1_hours = $shift1;
                $capacity->shift_2_hours = $shift2;
                $capacity->shift_3_hours = $shift3;
                $capacity->notes = $notes ?: null;
                $capacity->updated_by = auth()->id(); // Set updated_by untuk kebutuhan audit (Revisi #3 & Bonus)
                $capacity->save();
            }
        }

        return response()->json([
            'success' => true, 
            'message' => 'Data kapasitas terencana berhasil disimpan.'
        ]);
    }

    /**
     * Calculate summary metrics from grid data dataset.
     */
    private function calculateSummary(array $gridData, string $machineCode, $machines)
    {
        $totalCapacity = 0.0;
        $productionDays = 0;
        $holidayDays = 0;
        $totalOvertime = 0.0;

        foreach ($gridData as $row) {
            $total = (float)$row['total'];
            $totalCapacity += $total;

            if ($total > 0.0) {
                $productionDays++;
            } else {
                $holidayDays++;
            }

            // Jam Overtime per shift: SUM(MAX(0, shift1 - 7) + MAX(0, shift2 - 7) + MAX(0, shift3 - 7))
            $s1 = (float)$row['shift_1'];
            $s2 = (float)$row['shift_2'];
            $s3 = (float)$row['shift_3'];

            $ot1 = max(0.0, $s1 - 7.0);
            $ot2 = max(0.0, $s2 - 7.0);
            $ot3 = max(0.0, $s3 - 7.0);

            $totalOvertime += ($ot1 + $ot2 + $ot3);
        }

        $avgCapacity = $productionDays > 0 ? ($totalCapacity / $productionDays) : 0.0;

        // Resolve scope labels
        $scopeLine1 = 'GLOBAL';
        $scopeLine2 = 'Semua Mesin';

        if ($machineCode !== 'GLOBAL') {
            // Find active machine from mirror collection
            $activeMachine = $machines->first(function($m) use ($machineCode) {
                return strcasecmp($m->code, $machineCode) === 0;
            });

            $scopeLine1 = strtoupper($machineCode);
            $scopeLine2 = $activeMachine && !empty($activeMachine->name) ? $activeMachine->name : $scopeLine1;
        }

        return [
            'total_capacity' => $totalCapacity,
            'avg_capacity' => $avgCapacity,
            'production_days' => $productionDays,
            'holiday_days' => $holidayDays,
            'total_overtime' => $totalOvertime,
            'scope_line1' => $scopeLine1,
            'scope_line2' => $scopeLine2,
        ];
    }
}
