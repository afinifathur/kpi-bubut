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
        /**
         * 1. VALIDASI INPUT DASAR
         * Server = Source of Truth
         */
        $validated = $request->validate([
            'downtime_date' => 'required|date',
            'shift' => 'required|string|max:10',

            'machine_code' => 'required|string',
            'operator_code' => 'required|string',

            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',

            'reason' => 'required|string|max:255',
            'note' => 'nullable|string|max:255',
        ]);

        /**
         * 2. LOAD MASTER MIRROR (FAIL FAST)
         * Defensive Availability Layer
         */
        $machine = MdMachineMirror::where('code', $validated['machine_code'])
            ->where('status', 'active')
            ->firstOrFail();

        $operator = MdOperatorMirror::where('code', $validated['operator_code'])
            ->where('status', 'active')
            ->firstOrFail();

        /**
         * 3. HITUNG DURASI DOWNTIME (MENIT)
         */
        $start = strtotime($validated['time_start']);
        $end = strtotime($validated['time_end']);

        $durationMinutes = (int) round(($end - $start) / 60);

        if ($durationMinutes <= 0) {
            return back()
                ->withErrors(['time_end' => 'Durasi downtime tidak valid.'])
                ->withInput();
        }

        /**
         * 4. SIMPAN KE FACT TABLE (SNAPSHOT)
         * NO FK — KPI IMMUTABLE
         */
        DowntimeLog::create([
            'downtime_date' => $validated['downtime_date'],
            'shift' => $validated['shift'],

            'machine_code' => $this->normalizeCode($machine->code),
            'operator_code' => $this->normalizeCode($operator->code),

            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'duration_minutes' => $durationMinutes,

            'reason' => $validated['reason'],
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Downtime berhasil disimpan.');
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
