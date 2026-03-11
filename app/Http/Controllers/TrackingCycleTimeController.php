<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionLog;
use App\Models\MdItemMirror;
use App\Models\MdOperatorMirror;
use App\Models\MdMachineMirror;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CycleTimeExport;
use Carbon\Carbon;

class TrackingCycleTimeController extends Controller
{
    /**
     * ===============================
     * TRACKING CYCLE TIME PER ITEM
     * ===============================
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date') ?? date('Y-m-d');
        $endDate = $request->input('end_date') ?? date('Y-m-d');
        $itemCode = $request->input('item_code');

        // Validation 1: Max 180 Hari
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $diff = $start->diffInDays($end);

        if ($diff > 180) {
            return redirect()->route('tracking.cycle_time.index', [
                'start_date' => $startDate,
                'end_date' => $start->copy()->addDays(180)->format('Y-m-d'),
                'item_code' => $itemCode
            ])->with('error', 'Maksimal rentang tanggal adalah 180 hari. Tanggal akhir telah disesuaikan.');
        }

        // Initialize variables for the view
        $rows = [];
        $totalData = 0;
        $averageCycleTimeSec = 0;
        $selectedItem = null;

        if ($itemCode) {
            $selectedItem = MdItemMirror::where('code', $itemCode)->first();

            if ($selectedItem) {
                // Query ProductionLog for the selected item and date range
                // Exclude invalid cycle times if necessary, but here we take all to spot anomalies.
                // Depending on the precision, we may only consider entries where actual_qty > 0 
                // and cycle_time_used_sec > 0 to avoid division by zero or nonsensical averages.
                $query = ProductionLog::with(['machine', 'operator'])
                    ->where('item_code', $itemCode)
                    ->whereBetween('production_date', [$startDate, $endDate])
                    ->where('cycle_time_used_sec', '>', 0)
                    ->where('actual_qty', '>', 0);

                $rows = $query->orderBy('production_date', 'desc')
                    ->orderBy('time_start', 'desc')
                    ->get();

                $totalData = $rows->count();

                if ($totalData > 0) {
                    // Calculate average cycle time per piece (total cycle time / total actual qty) OR average of user inputs
                    // In the production form, 'cycle_time_used_sec' is usually the standard cycle time or inputted cycle time.
                    // We will average the cycle_time_used_sec directly.
                    $averageCycleTimeSec = $rows->avg('cycle_time_used_sec');
                }
            }
        }

        // Just fetching items for the initial dropdown might be heavy if there are thousands.
        // It's better to use select2 ajax search like in the other pages, 
        // but for the initial load if we have an item code, get its name.

        return view('tracking.cycle_time.index', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'itemCode' => $itemCode,
            'selectedItem' => $selectedItem,
            'totalData' => $totalData,
            'averageCycleTimeSec' => $averageCycleTimeSec,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date') ?? date('Y-m-d');
        $endDate = $request->input('end_date') ?? date('Y-m-d');
        $itemCode = $request->input('item_code');

        if (!$itemCode) {
            return redirect()->back()->with('error', 'Silakan pilih barang terlebih dahulu untuk Export PDF.');
        }

        $query = ProductionLog::with(['machine', 'operator'])
            ->where('item_code', $itemCode)
            ->whereBetween('production_date', [$startDate, $endDate])
            ->where('cycle_time_used_sec', '>', 0)
            ->where('actual_qty', '>', 0);

        $rows = $query->orderBy('production_date', 'desc')
            ->orderBy('time_start', 'desc')
            ->get();

        $selectedItem = MdItemMirror::where('code', $itemCode)->first();
        $totalData = $rows->count();
        $averageCycleTimeSec = $totalData > 0 ? $rows->avg('cycle_time_used_sec') : 0;

        $pdf = Pdf::loadView('tracking.cycle_time.pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'itemCode' => $itemCode,
            'selectedItem' => $selectedItem,
            'rows' => $rows,
            'averageCycleTimeSec' => $averageCycleTimeSec,
            'totalData' => $totalData
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('Cycle-Time-Report-' . $itemCode . '-' . $startDate . '-to-' . $endDate . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date') ?? date('Y-m-d');
        $endDate = $request->input('end_date') ?? date('Y-m-d');
        $itemCode = $request->input('item_code');

        if (!$itemCode) {
            return redirect()->back()->with('error', 'Silakan pilih barang terlebih dahulu untuk Export Excel.');
        }

        $filename = "Cycle-Time-Report-{$itemCode}-{$startDate}-to-{$endDate}.xlsx";

        return Excel::download(
            new CycleTimeExport($startDate, $endDate, $itemCode),
            $filename
        );
    }
}
