<?php

namespace App\Http\Controllers;

use App\Models\HrReport;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class HrReportController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('q');
        
        $sort = $request->get('sort', 'report_number');
        if (!in_array($sort, ['report_number', 'report_date', 'category', 'title', 'status', 'approval_status'])) {
            $sort = 'report_number';
        }
        
        $direction = $request->get('direction', 'desc');
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $countsQuery = HrReport::selectRaw("
            COUNT(CASE WHEN status = 'Closed' THEN 1 END) as closed,
            COUNT(CASE WHEN status IN ('Investigating', 'Action Plan', 'Monitoring') THEN 1 END) as in_progress,
            COUNT(CASE WHEN status = 'Open' THEN 1 END) as not_started
        ")->first();

        $counts = [
            'closed' => $countsQuery->closed ?? 0,
            'in_progress' => $countsQuery->in_progress ?? 0,
            'not_started' => $countsQuery->not_started ?? 0,
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
        // Only HR Admin can create
        if (!auth()->user()->isHrAdmin()) {
            abort(403, 'Hanya Admin HR yang dapat membuat laporan baru.');
        }

        $now = Carbon::now();
        $lastReport = HrReport::whereMonth('report_date', $now->month)->whereYear('report_date', $now->year)->orderBy('id', 'desc')->first();
        $nextNumber = 1;
        if ($lastReport) {
            $parts = explode('/', $lastReport->report_number);
            $nextNumber = (int) end($parts) + 1;
        }
        $reportNumber = sprintf('HR/KPI-bubut/%s/%s/%03d', $now->format('Y'), $now->format('m'), $nextNumber);
        $today = $now->format('Y-m-d');
        return view('hr_report.create', compact('reportNumber', 'today'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isHrAdmin()) {
            abort(403, 'Hanya Admin HR yang dapat membuat laporan.');
        }

        $rules = [
            'category' => 'required|string',
            'operator_name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'data_link' => 'required|url',
            'additional_notes' => 'nullable|string',
            'evidence_files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];

        $validated = $request->validate($rules);

        return DB::transaction(function() use ($validated, $request) {
            $now = Carbon::now();
            $lastReport = HrReport::whereMonth('report_date', $now->month)->whereYear('report_date', $now->year)->orderBy('id', 'desc')->lockForUpdate()->first();
            $nextNumber = 1;
            if ($lastReport) {
                $parts = explode('/', $lastReport->report_number);
                $nextNumber = (int) end($parts) + 1;
            }
            
            $validated['report_number'] = sprintf('HR/KPI-bubut/%s/%s/%03d', $now->format('Y'), $now->format('m'), $nextNumber);
            $validated['report_date'] = $now->format('Y-m-d');
            $validated['status'] = 'Open';
            $validated['approval_status'] = 'draft';
            $validated['created_by'] = Auth::id();

            if ($request->hasFile('evidence_files')) {
                $validated['evidence_files'] = $this->handleFileUploads($request->file('evidence_files'));
            }

            $report = HrReport::create($validated);

            AuditLog::create([
                'user_id' => Auth::id(), 'user_name' => Auth::user()->name, 'role' => Auth::user()->role,
                'ip_address' => $request->ip(), 'action' => 'CREATE_HR_REPORT', 'model' => 'HrReport',
                'details' => ['id' => $report->id, 'report_number' => $report->report_number]
            ]);

            return redirect()->route('hr_report.show', $report->id)->with('success', 'Laporan berhasil dibuat.');
        });
    }

    public function show($id)
    {
        $report = HrReport::with(['creator', 'approver'])->findOrFail($id);
        return view('hr_report.show', compact('report'));
    }

    public function edit($id)
    {
        if (!auth()->user()->isHrAdmin()) {
            abort(403, 'Hanya Admin HR yang dapat mengedit laporan.');
        }

        $report = HrReport::findOrFail($id);
        
        // Guard: Locked if submitted or approved
        if (in_array($report->approval_status, ['submitted', 'approved'])) {
            abort(403, 'Laporan sudah dikunci dan tidak dapat diedit.');
        }

        return view('hr_report.edit', compact('report'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->isHrAdmin()) {
            abort(403, 'Hanya Admin HR yang dapat memperbarui laporan.');
        }

        $report = HrReport::findOrFail($id);

        if (in_array($report->approval_status, ['submitted', 'approved'])) {
            abort(403, 'Laporan terkunci.');
        }

        $rules = [
            'category' => 'required|string',
            'operator_name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'data_link' => 'required|url',
            'root_cause' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'target_completion_date' => 'nullable|date',
            'monitoring_result' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'evidence_files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'remove_existing_files' => 'nullable|array',
            'remove_existing_files.*' => 'string',
        ];

        $validated = $request->validate($rules);
        $changes = [];
        foreach ($validated as $k => $v) {
            if ($k !== 'evidence_files' && $report->$k != $v) $changes[$k] = ['old' => $report->$k, 'new' => $v];
        }

        $files = $report->evidence_files ?? [];
        $removedFileNames = [];
        
        // 1. Handle removal of existing files
        if ($request->has('remove_existing_files')) {
            $filesToRemove = $request->input('remove_existing_files');
            
            // Whitelist: only allow deletion of paths currently in this report
            $existingPaths = array_column($files, 'path');

            foreach ($filesToRemove as $path) {
                if (in_array($path, $existingPaths)) {
                    // Find name for logging before deletion
                    foreach ($files as $f) {
                        if ($f['path'] === $path) {
                            $removedFileNames[] = $f['name'];
                            break;
                        }
                    }

                    // Delete from physical storage
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                    }
                    
                    // Remove from array
                    $files = array_filter($files, function($file) use ($path) {
                        return $file['path'] !== $path;
                    });
                }
            }
            // Re-index array
            $files = array_values($files);
            $changes['evidence_files_removed'] = $removedFileNames;
        }

        // 2. Handle new file uploads
        if ($request->hasFile('evidence_files')) {
            $newFiles = $this->handleFileUploads($request->file('evidence_files'));
            $files = array_merge($files, $newFiles);
            $changes['evidence_files_added'] = array_column($newFiles, 'name');
        }
        $validated['evidence_files'] = $files;

        $report->update($validated);

        if (!empty($changes)) {
            AuditLog::create([
                'user_id' => Auth::id(), 'user_name' => Auth::user()->name, 'role' => Auth::user()->role,
                'ip_address' => $request->ip(), 'action' => 'UPDATE_HR_REPORT', 'model' => 'HrReport',
                'details' => ['id' => $report->id, 'changes' => $changes]
            ]);
        }

        return redirect()->route('hr_report.show', $id)->with('success', 'Laporan berhasil diperbarui.');
    }

    public function updateStatus(Request $request, $id)
    {
        // Only HR Admin can update operational status
        if (!auth()->user()->isHrAdmin()) {
            abort(403, 'Hanya Admin HR yang dapat mengubah status operasional.');
        }

        $report = HrReport::findOrFail($id);
        
        $request->validate(['status' => 'required|in:Open,Investigating,Action Plan,Monitoring']);

        if ($report->approval_status === 'submitted' || $report->approval_status === 'approved') {
            return back()->with('error', 'Status tidak dapat diubah saat dalam review atau sudah disetujui.');
        }

        $oldStatus = $report->status;
        $report->update(['status' => $request->status]);

        AuditLog::create([
            'user_id' => Auth::id(), 'user_name' => Auth::user()->name, 'role' => Auth::user()->role,
            'ip_address' => $request->ip(), 'action' => 'STATUS_CHANGE_HR_REPORT', 'model' => 'HrReport',
            'details' => ['id' => $report->id, 'old_status' => $oldStatus, 'new_status' => $request->status]
        ]);

        return back()->with('success', 'Status operasional diperbarui.');
    }

    public function approve(Request $request, $id)
    {
        if (!auth()->user()->isHrManager()) abort(403);
        $report = HrReport::findOrFail($id);
        if ($report->approval_status !== 'submitted') return back()->with('error', 'Hanya laporan Submitted.');

        $request->validate(['note' => 'nullable|string|max:1000']);

        $report->update([
            'approval_status' => 'approved',
            'status' => 'Closed', 
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_note' => $request->note
        ]);

        AuditLog::create([
            'user_id' => Auth::id(), 'user_name' => Auth::user()->name, 'role' => Auth::user()->role,
            'ip_address' => $request->ip(), 'action' => 'APPROVE_HR_REPORT', 'model' => 'HrReport',
            'details' => ['id' => $report->id, 'note' => $request->note]
        ]);
        return back()->with('success', 'Laporan telah DISETUJUI.');
    }

    public function reject(Request $request, $id)
    {
        if (!auth()->user()->isHrManager()) abort(403);
        $report = HrReport::findOrFail($id);
        if ($report->approval_status !== 'submitted') return back()->with('error', 'Hanya laporan Submitted.');
        
        $request->validate(['note' => 'required|string|min:5|max:1000']);

        $report->update([
            'approval_status' => 'rejected',
            'status' => 'Monitoring', 
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_note' => $request->note
        ]);

        AuditLog::create([
            'user_id' => Auth::id(), 'user_name' => Auth::user()->name, 'role' => Auth::user()->role,
            'ip_address' => $request->ip(), 'action' => 'REJECT_HR_REPORT', 'model' => 'HrReport',
            'details' => ['id' => $report->id, 'note' => $request->note]
        ]);
        return back()->with('success', 'Laporan ditolak.');
    }

    public function submitForApproval(Request $request, $id)
    {
        if (!auth()->user()->isHrAdmin()) abort(403, 'Hanya Admin HR.');
        $report = HrReport::findOrFail($id);
        if (!in_array($report->approval_status, ['draft', 'rejected'])) return back()->with('error', 'Status tidak valid.');

        if (empty($report->root_cause) || empty($report->corrective_action) || empty($report->target_completion_date)) {
            return back()->with('error', 'Lengkapi data Analisis dan Tindakan sebelum mengajukan.');
        }

        $report->update([
            'approval_status' => 'submitted',
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
            'approved_by' => null, 'approved_at' => null, 'approval_note' => null
        ]);

        AuditLog::create([
            'user_id' => Auth::id(), 'user_name' => Auth::user()->name, 'role' => Auth::user()->role,
            'ip_address' => $request->ip(), 'action' => 'SUBMIT_FOR_APPROVAL_HR_REPORT', 'model' => 'HrReport',
            'details' => ['id' => $report->id]
        ]);
        return back()->with('success', 'Laporan diajukan.');
    }

    public function destroy(Request $request, $id)
    {
        if (!auth()->user()->isHrAdmin()) {
            abort(403, 'Hanya Admin HR yang dapat menghapus laporan.');
        }

        $report = HrReport::findOrFail($id);
        if (in_array($report->approval_status, ['submitted', 'approved']) || $report->status === 'Closed') {
            return back()->with('error', 'Laporan terkunci.');
        }

        AuditLog::create([
            'user_id' => Auth::id(), 'user_name' => Auth::user()->name, 'role' => Auth::user()->role,
            'ip_address' => $request->ip(), 'action' => 'DELETE_HR_REPORT', 'model' => 'HrReport',
            'details' => ['id' => $report->id, 'report_number' => $report->report_number]
        ]);

        if ($report->evidence_files) {
            foreach ($report->evidence_files as $file) Storage::disk('public')->delete($file['path']);
        }

        $report->delete();
        return redirect()->route('hr_report.index')->with('success', 'Laporan dihapus.');
    }

    public function exportPdf(Request $request, $id)
    {
        $report = HrReport::with(['creator', 'approver'])->findOrFail($id);
        $isDraft = ($report->approval_status !== 'approved');
        $pdf = Pdf::loadView('hr_report.pdf', ['report' => $report, 'isDraft' => $isDraft]);
        $pdf->setPaper('A4', 'portrait');
        $filename = str_replace('/', '-', $report->report_number) . ($isDraft ? '-DRAFT' : '') . '.pdf';
        return $pdf->stream($filename);
    }

    private function handleFileUploads($uploadedFiles) {
        $files = [];
        foreach ($uploadedFiles as $file) {
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('hr_reports/evidence', $filename, 'public');
            $files[] = ['name' => $file->getClientOriginalName(), 'path' => $path];
        }
        return $files;
    }
}
