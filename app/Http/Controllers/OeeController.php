<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OeeService;
use App\Models\MdMachineMirror;
use App\Exports\OeeExport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class OeeController extends Controller
{
    protected OeeService $oeeService;

    public function __construct(OeeService $oeeService)
    {
        $this->oeeService = $oeeService;
    }

    /**
     * Display OEE summarized report page
     */
    public function index(Request $request)
    {
        try {
            $data = $this->resolveReportData($request);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        // Fetch Active Machines for Dropdown Selection
        $data['machines'] = MdMachineMirror::where('department_code', $data['departmentCode'])
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['code', 'name']);

        return view('oee.index', $data);
    }

    /**
     * Export report data as Excel Spreadsheet
     */
    public function exportExcel(Request $request)
    {
        try {
            $data = $this->resolveReportData($request);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses export: ' . $e->getMessage());
        }

        // TASK 4: Empty export guard
        if (empty($data['rows'])) {
            return redirect()->back()
                ->with('warning', 'Tidak ada data OEE untuk diexport.');
        }

        // TASK 1: Export filename governance
        $scopeLabel = $data['selectedMachine']
            ? strtoupper(str_replace(' ', '_', $data['selectedMachine']))
            : 'ALL';

        return Excel::download(
            new OeeExport($data),
            'Laporan_OEE_' . $scopeLabel . '_' . $data['startDate'] . '_to_' . $data['endDate'] . '.xlsx'
        );
    }

    /**
     * Export report data as Landscape PDF Document
     */
    public function exportPdf(Request $request)
    {
        try {
            $data = $this->resolveReportData($request);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses export: ' . $e->getMessage());
        }

        // TASK 4: Empty export guard
        if (empty($data['rows'])) {
            return redirect()->back()
                ->with('warning', 'Tidak ada data OEE untuk diexport.');
        }

        // TASK 1: Export filename governance
        $scopeLabel = $data['selectedMachine']
            ? strtoupper(str_replace(' ', '_', $data['selectedMachine']))
            : 'ALL';

        // TASK 5: Explicit PDF landscape governance
        $pdf = Pdf::loadView('exports.oee_pdf', $data)
            ->setPaper('a4', 'landscape');

        // TASK 2: PDF Stream mode
        return $pdf->stream('Laporan_OEE_' . $scopeLabel . '_' . $data['startDate'] . '_to_' . $data['endDate'] . '.pdf');
    }

    // GOVERNANCE:
    // exports intentionally reuse identical service dataset
    // to guarantee dashboard/export parity consistency
    protected function resolveReportData(Request $request): array
    {
        // 1. Resolve Active Department context from Auth Session
        $user = auth()->user();
        $departmentCode = session('selected_department_code');
        
        if (empty($departmentCode) || $departmentCode === 'all') {
            $departmentCode = $user->department_code;
        }

        if (empty($departmentCode)) {
            throw new \Exception('Departemen aktif tidak ditemukan.');
        }

        // 2. Setup Default Dates if missing from request before validation
        if (!$request->has('start_date')) {
            $request->merge(['start_date' => date('Y-m-d')]);
        }
        if (!$request->has('end_date')) {
            $request->merge(['end_date' => date('Y-m-d')]);
        }

        // 3. Formally validate date inputs via Laravel Validator
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $today = date('Y-m-d');
        if ($endDate > $today) {
            $endDate = $today;
        }
        $machineCode = $request->input('machine_code');

        if ($machineCode === 'all') {
            $machineCode = null;
        }

        // 4. Secure Machine Scope Validation
        if ($machineCode !== null) {
            $machineExists = MdMachineMirror::where('department_code', $departmentCode)
                ->where('status', 'active')
                ->where('code', $machineCode)
                ->exists();

            if (!$machineExists) {
                throw new \Exception('Mesin tidak valid atau tidak berada dalam departemen aktif.');
            }
        }

        // 5. Business Logic Validation (Inclusive Off-by-one Limit Check)
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($end->lt($start)) {
            throw new \Exception('Tanggal selesai tidak boleh mendahului tanggal mulai.');
        }

        if (($start->diffInDays($end) + 1) > 45) {
            throw new \Exception('Rentang tanggal maksimal yang diperbolehkan adalah 45 hari (inklusif).');
        }

        // 6. Fetch OEE dataset directly via centralized OeeService contract
        $reportPackage = $this->oeeService->getOeeReport($startDate, $endDate, $departmentCode, $machineCode);

        // TASK 3: Inject Audit Metadata into export payload
        return [
            'rows' => $reportPackage['rows'],
            'summary' => $reportPackage['summary'],
            'rowCount' => $reportPackage['row_count'],
            'topDowntimeReasons' => $reportPackage['top_downtime_reasons'],
            'topRejectReasons' => $reportPackage['top_reject_reasons'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedMachine' => $machineCode,
            'departmentCode' => $departmentCode,
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'generated_ip' => $request->ip(),
            'generated_by' => auth()->user()->name,
        ];
    }
}
