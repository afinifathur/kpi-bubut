<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reports\LeaderboardReport;
use App\Exports\LeaderboardExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class LeaderboardController extends Controller
{
    /**
     * Display the operator leaderboard.
     */
    public function index(Request $request)
    {
        $endDate = $request->get('end_date', date('Y-m-d'));
        $startDate = $request->get('start_date', Carbon::parse($endDate)->subDays(30)->format('Y-m-d'));
        $operatorCode = $request->get('operator_code');

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            return redirect()->route('leaderboard.index', [
                'start_date' => $endDate,
                'end_date' => $startDate,
                'operator_code' => $operatorCode
            ])->with('error', 'Tanggal mulai tidak boleh melebihi tanggal akhir.');
        }

        // Validate max range: 366 days
        if ($start->diffInDays($end) > 366) {
            return redirect()->route('leaderboard.index', [
                'start_date' => $startDate,
                'end_date' => $start->copy()->addDays(366)->format('Y-m-d'),
                'operator_code' => $operatorCode
            ])->with('error', 'Maksimal rentang tanggal adalah 366 hari. Tanggal akhir telah disesuaikan.');
        }

        $report = new LeaderboardReport($request);
        $dates = $report->getDates();
        $leaderboardData = $report->getData();
        $operatorNames = $report->getOperatorNames();

        return view('leaderboard.index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedOperator' => $operatorCode,
            'operatorNames' => $operatorNames,
            'dates' => $dates,
            'leaderboardData' => $leaderboardData,
        ]);
    }

    /**
     * Export leaderboard to PDF.
     */
    public function exportPdf(Request $request)
    {
        $endDate = $request->get('end_date', date('Y-m-d'));
        $startDate = $request->get('start_date', Carbon::parse($endDate)->subDays(30)->format('Y-m-d'));

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end) || $start->diffInDays($end) > 366) {
            return redirect()->route('leaderboard.index')->with('error', 'Filter tanggal tidak valid untuk export.');
        }

        $report = new LeaderboardReport($request);
        $dates = $report->getDates();
        $leaderboardData = $report->getData();

        if (empty($leaderboardData)) {
            return redirect()->back()->with('warning', 'Tidak ada data untuk diexport.');
        }

        // Calculate columns to determine paper size
        // totalColumns = summaryColumns (Rank, Name, Code, Avg, Days = 5) + dailyColumns (count($dates))
        $summaryColumns = 5;
        $dailyColumns = count($dates);
        $totalColumns = $summaryColumns + $dailyColumns;

        // Automatically switch to Folio Landscape if too wide (> 20 columns)
        $paperSize = $totalColumns > 20 ? [0, 0, 612, 936] : 'a4';

        $reportTitle = 'KPI Bubut Leaderboard';

        $pdf = Pdf::loadView('exports.leaderboard_pdf', [
            'reportTitle' => $reportTitle,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dates' => $dates,
            'leaderboardData' => $leaderboardData,
            'departmentName' => $report->getDepartmentName(),
            'departmentCode' => $report->getDepartmentCode(),
            'generated_by' => auth()->user()->name ?? auth()->user()->email,
            'generated_at' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i:s'),
        ]);

        $pdf->setPaper($paperSize, 'landscape');
        $pdf->setOption('enable_php', true);

        $filename = str_replace(' ', '_', $reportTitle) . '_' . $startDate . '_to_' . $endDate . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Export leaderboard to Excel.
     */
    public function exportExcel(Request $request)
    {
        $endDate = $request->get('end_date', date('Y-m-d'));
        $startDate = $request->get('start_date', Carbon::parse($endDate)->subDays(30)->format('Y-m-d'));

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end) || $start->diffInDays($end) > 366) {
            return redirect()->route('leaderboard.index')->with('error', 'Filter tanggal tidak valid untuk export.');
        }

        $report = new LeaderboardReport($request);
        $dates = $report->getDates();
        $leaderboardData = $report->getData();

        if (empty($leaderboardData)) {
            return redirect()->back()->with('warning', 'Tidak ada data untuk diexport.');
        }

        $reportTitle = 'KPI Bubut Leaderboard';
        $filename = str_replace(' ', '_', $reportTitle) . '_' . $startDate . '_to_' . $endDate . '.xlsx';

        return Excel::download(
            new LeaderboardExport($dates, $leaderboardData),
            $filename
        );
    }
}

