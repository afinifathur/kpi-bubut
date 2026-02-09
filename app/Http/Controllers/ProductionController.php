<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use App\Models\ProductionLog;

// MASTER MIRROR (READ ONLY - SSOT)
use App\Models\MdItemMirror;
use App\Models\MdMachineMirror;
use App\Models\MdOperatorMirror;

class ProductionController extends Controller
{
    /**
     * =================================
     * FORM INPUT PRODUKSI
     * =================================
     */
    public function create()
    {
        // Items and Operators are now loaded via Autocomplete API
        return view('production.input', [
            'machines' => MdMachineMirror::where('status', 'active')
                ->orderBy('code')
                ->get(['code', 'name']),
        ]);
    }

    /**
     * =================================
     * SIMPAN DATA PRODUKSI (HARD STOP)
     * =================================
     */
    public function store(Request $request)
    {
        if (auth()->user()->isReadOnly()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menyimpan data (Read-Only).');
        }
        /**
         * ===============================
         * DEBUG PALING CEPAT (SEMENTARA)
         * ===============================
         * AKTIFKAN JIKA ADA ERROR FORM
         * Setelah ketemu masalah → HAPUS BARIS INI
         */
        //dd($request->all());

        /**
         * 1. VALIDASI INPUT DASAR
         * Server = Source of Truth
         */
        $validated = $request->validate([
            'production_date' => 'required|date',
            'shift' => 'required|string|max:10',

            'operator_code' => 'required|string',
            'machine_code' => 'required|string',
            'item_code' => 'required|string',
            'heat_number' => 'nullable|string',

            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i', // Removed after:time_start to allow next day

            'cycle_time_minutes' => 'required|integer|min:0',
            'cycle_time_seconds' => 'required|integer|min:0|max:59',

            'actual_qty' => 'required|integer|min:0',
            'remark' => 'nullable|string|max:50',
        ]);

        /**
         * 2. LOAD MASTER MIRROR (FAIL FAST)
         * Defensive KPI Layer
         */
        $item = MdItemMirror::where('code', $validated['item_code'])
            ->where('status', 'active')
            ->firstOrFail();

        $machine = MdMachineMirror::where('code', $validated['machine_code'])
            ->where('status', 'active')
            ->firstOrFail();

        $operator = MdOperatorMirror::where('code', $validated['operator_code'])
            ->where('status', 'active')
            ->firstOrFail();

        /**
         * 3. HITUNG DURASI KERJA
         * Handle Cross-Day Shift (Misal Shift 3: 23:00 - 07:00)
         */
        $startSeconds = strtotime($validated['time_start']);
        $endSeconds = strtotime($validated['time_end']);

        if ($endSeconds < $startSeconds) {
            $endSeconds += 86400; // Add 24 hours (1 day)
        }

        $workSeconds = $endSeconds - $startSeconds;

        if ($workSeconds <= 0) {
            throw ValidationException::withMessages([
                'time_end' => 'Jam selesai harus lebih besar dari jam mulai.',
            ]);
        }

        $workHours = round($workSeconds / 3600, 2);

        /**
         * 4. HITUNG CYCLE TIME MANUAL
         * User input: Minutes & Seconds -> Total Seconds
         */
        $cycleTimeSec = ($validated['cycle_time_minutes'] * 60) + $validated['cycle_time_seconds'];

        if ($cycleTimeSec <= 0) {
            throw ValidationException::withMessages([
                'cycle_time_seconds' => 'Total Cycle Time tidak boleh 0 detik.',
            ]);
        }

        /**
         * 5. HITUNG TARGET PRODUKSI
         */
        $targetQty = intdiv($workSeconds, $cycleTimeSec);

        /**
         * 6. HITUNG ACHIEVEMENT
         */
        $actualQty = (int) $validated['actual_qty'];

        $achievementPercent = $targetQty > 0
            ? round(($actualQty / $targetQty) * 100, 2)
            : 0;

        // Fetch Heat Number details if exists
        $heatNumberDetails = null;
        if (!empty($validated['heat_number'])) {
            $heatNumberDetails = \App\Models\MdHeatNumberMirror::where('heat_number', $validated['heat_number'])->first();
        }

        /**
         * 7. SIMPAN KE FACT TABLE (IMMUTABLE KPI)
         * NO FK — SNAPSHOT ONLY
         */
        ProductionLog::create([
            'production_date' => $validated['production_date'],
            'shift' => $validated['shift'],

            'operator_code' => $this->normalizeCode($operator->code),
            'machine_code' => $this->normalizeCode($machine->code),
            'item_code' => $this->normalizeCode($item->code),
            'heat_number' => $validated['heat_number'] ?? null,
            'size' => $heatNumberDetails ? $heatNumberDetails->size : null,
            'customer' => $heatNumberDetails ? $heatNumberDetails->customer : null,
            'line' => $heatNumberDetails ? $heatNumberDetails->line : null,

            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'work_hours' => $workHours,

            // SNAPSHOT NILAI KRITIS (MANUAL INPUT)
            'cycle_time_used_sec' => $cycleTimeSec,
            'target_qty' => $targetQty,
            'actual_qty' => $actualQty,
            'achievement_percent' => $achievementPercent,
            'remark' => $validated['remark'] ?? null,
        ]);

        // Regenerate KPI (Daily Recap)
        \App\Services\DailyKpiService::generateOperatorDaily($validated['production_date']);
        \App\Services\DailyKpiService::generateMachineDaily($validated['production_date']);

        return redirect()
            ->back()
            ->with('success', "Data produksi a.n. {$operator->name} berhasil disimpan.");
    }

    /**
     * =================================
     * HELPER NORMALISASI KODE
     * =================================
     */
    private function normalizeCode(string $value): string
    {
        return strtolower(trim($value));
    }
}
