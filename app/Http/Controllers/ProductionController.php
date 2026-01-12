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
            'time_end' => 'required|date_format:H:i|after:time_start',

            'actual_qty' => 'required|integer|min:0',
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
         */
        $workSeconds = strtotime($validated['time_end'])
            - strtotime($validated['time_start']);

        if ($workSeconds <= 0) {
            throw ValidationException::withMessages([
                'time_end' => 'Jam selesai harus lebih besar dari jam mulai.',
            ]);
        }

        $workHours = round($workSeconds / 3600, 2);

        /**
         * 4. SNAPSHOT CYCLE TIME (HANYA DARI MIRROR)
         */
        $cycleTimeSec = (int) $item->cycle_time_sec;

        if ($cycleTimeSec <= 0) {
            throw ValidationException::withMessages([
                'item_code' => 'Cycle time item tidak valid.',
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

            'time_start' => $validated['time_start'],
            'time_end' => $validated['time_end'],
            'work_hours' => $workHours,

            // SNAPSHOT NILAI KRITIS
            'cycle_time_used_sec' => $cycleTimeSec,
            'target_qty' => $targetQty,
            'actual_qty' => $actualQty,
            'achievement_percent' => $achievementPercent,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Data produksi berhasil disimpan.');
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
