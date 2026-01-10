<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// FACT TABLE
use App\Models\RejectLog;

// MASTER MIRROR (READ ONLY - SSOT)
use App\Models\MdItemMirror;
use App\Models\MdMachineMirror;
use App\Models\MdOperatorMirror;

class RejectController extends Controller
{
    /**
     * ===============================
     * LIST DATA REJECT
     * ===============================
     */
    public function index()
    {
        $rows = RejectLog::orderBy('reject_date', 'desc')->get();

        return view('reject.index', [
            'rows' => $rows,
        ]);
    }

    /**
     * ===============================
     * FORM INPUT REJECT
     * ===============================
     */
    public function create()
    {
        return view('reject.input', [
            'machines' => MdMachineMirror::where('status', 'active')
                ->orderBy('code')
                ->get(['code', 'name']),
        ]);
    }

    /**
     * ===============================
     * SIMPAN DATA REJECT (HARD STOP)
     * ===============================
     */
    public function store(Request $request)
    {
        /**
         * 1. VALIDASI INPUT DASAR
         * Server = Source of Truth
         */
        $validated = $request->validate([
            'reject_date' => 'required|date',
            'operator_code' => 'required|string',
            'machine_code' => 'required|string',
            'item_code' => 'required|string',
            'reject_qty' => 'required|integer|min:1',
            'reject_reason' => 'required|string|max:255',
            'note' => 'nullable|string',
        ]);

        /**
         * 2. LOAD MASTER MIRROR (FAIL FAST)
         * Defensive Quality Layer
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
         * 3. SIMPAN KE FACT TABLE (REJECT SNAPSHOT)
         * NO FK — TIDAK MENGUBAH KPI PRODUKSI
         */
        RejectLog::create([
            'reject_date' => $validated['reject_date'],

            'operator_code' => $this->normalizeCode($operator->code),
            'machine_code' => $this->normalizeCode($machine->code),
            'item_code' => $this->normalizeCode($item->code),

            'reject_qty' => $validated['reject_qty'],
            'reject_reason' => $validated['reject_reason'],
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Data reject berhasil disimpan.');
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
