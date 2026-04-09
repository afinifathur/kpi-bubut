<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// FACT TABLE
use App\Models\DowntimeLog;

// MASTER MIRROR (READ ONLY - SSOT)
use App\Models\MdMachineMirror;
use App\Models\MdOperatorMirror;

class DowntimeController extends Controller
{
    /**
     * ===============================
     * FORM INPUT DOWNTIME
     * ===============================
     */
    public function create()
    {
        return view('downtime.input', [
            'machines' => MdMachineMirror::where('status', 'active')
                ->orderBy('code')
                ->get(['code', 'name']),
        ]);
    }

    /**
     * ===============================
     * SIMPAN DOWNTIME (TIME-BASED, HARD STOP)
     * ===============================
     */
    public function store(Request $request)
    {
        if (auth()->user()->isReadOnly()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses (Read-Only).');
        }

        $entryType = $request->input('entry_type', 'downtime');

        /**
         * 1. VALIDASI INPUT BERDASARKAN TIPE
         */
        $rules = [
            'entry_type' => 'required|in:downtime,check',
            'downtime_date' => 'required|date',
            'machine_code' => 'required|string',
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

        /**
         * 2. LOAD MASTER MIRROR (FAIL FAST)
         */
        $machine = MdMachineMirror::where('code', $validated['machine_code'])
            ->where('status', 'active')
            ->firstOrFail();

        /**
         * 3. DATA PREPARATION
         */
        $data = [
            'entry_type' => $entryType,
            'department_code' => $machine->department_code, // Use machine's dept
            'downtime_date' => $validated['downtime_date'],
            'machine_code' => $this->normalizeCode($machine->code),
            'note' => $validated['note'] ?? null,
        ];

        if ($entryType === 'downtime') {
            $start = strtotime($validated['start_time']);
            $end = strtotime($validated['end_time']);
            $durationMinutes = (int) round(($end - $start) / 60);

            $data += [
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'duration_minutes' => $durationMinutes,
                'reason' => $validated['reason'],
            ];
        } else {
            $data += [
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
                'duration_minutes' => 0, // Daily check doesn't have "duration" in the same sense
            ];
        }

        /**
         * 4. SIMPAN KE FACT TABLE
         */
        DowntimeLog::create($data);

        $msg = $entryType === 'downtime' ? 'Data Downtime' : 'Laporan Pengecekan Harian';

        return redirect()
            ->back()
            ->with('success', "{$msg} Mesin {$machine->name} berhasil disimpan.");
    }

    /**
     * ===============================
     * HELPER NORMALISASI KODE
     * ===============================
     */
    private function normalizeCode(string $value): string
    {
        return strtolower(trim($value));
    }
}
