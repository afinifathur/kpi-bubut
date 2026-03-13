<?php

namespace App\Http\Controllers;

use App\Models\HrReport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class HrReportController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('q');
        $sort = $request->get('sort', 'report_number');
        $direction = $request->get('direction', 'desc');

        // Status Counts
        $allReports = HrReport::all();
        $counts = [
            'closed' => $allReports->where('status', 'Closed')->count(),
            'in_progress' => $allReports->whereIn('status', ['Investigating', 'Action Plan', 'Monitoring'])->count(),
            'not_started' => $allReports->where('status', 'Open')->count(),
        ];

        $query = HrReport::with('creator');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('report_number', 'LIKE', "%{$search}%")
                  ->orWhere('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $reports = $query->orderBy($sort, $direction)->get();

        return view('hr_report.index', compact('reports', 'counts', 'search', 'sort', 'direction'));
    }

    public function create()
    {
        $now = Carbon::now();
        $month = $now->format('m');
        
        // Generate automatic report number: HR/KPI-bubut/MM/NNN
        $lastReport = HrReport::whereMonth('report_date', $now->month)
            ->whereYear('report_date', $now->year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastReport) {
            $parts = explode('/', $lastReport->report_number);
            $nextNumber = (int) end($parts) + 1;
        }

        $reportNumber = sprintf('HR/KPI-bubut/%s/%03d', $month, $nextNumber);
        $today = $now->format('Y-m-d');

        return view('hr_report.create', compact('reportNumber', 'today'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_number' => 'required|string|unique:hr_reports',
            'report_date' => 'required|date',
            'category' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'data_link' => 'nullable|url',
            'root_cause' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'status' => 'required|in:Open,Investigating,Action Plan,Monitoring,Closed',
        ]);

        $validated['created_by'] = Auth::id();

        HrReport::create($validated);

        return redirect()->route('hr_report.index')->with('success', 'Laporan HR berhasil dibuat.');
    }

    public function show($id)
    {
        $report = HrReport::with('creator')->findOrFail($id);
        return view('hr_report.show', compact('report'));
    }

    public function edit($id)
    {
        $report = HrReport::findOrFail($id);
        return view('hr_report.edit', compact('report'));
    }

    public function update(Request $request, $id)
    {
        $report = HrReport::findOrFail($id);

        $validated = $request->validate([
            'category' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'data_link' => 'nullable|url',
            'root_cause' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'status' => 'required|in:Open,Investigating,Action Plan,Monitoring,Closed',
        ]);

        $report->update($validated);

        return redirect()->route('hr_report.show', $id)->with('success', 'Laporan HR berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $report = HrReport::findOrFail($id);
        $report->delete();

        return redirect()->route('hr_report.index')->with('success', 'Laporan berhasil dihapus.');
    }

    public function exportPdf($id)
    {
        $report = HrReport::with('creator')->findOrFail($id);

        $pdf = Pdf::loadView('hr_report.pdf', [
            'report' => $report,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = str_replace('/', '-', $report->report_number) . '.pdf';
        return $pdf->stream($filename);
    }
}
