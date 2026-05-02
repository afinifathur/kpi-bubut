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
        if (!auth()->user()->canManageHrReports()) {
            abort(403, 'Anda tidak memiliki akses untuk membuat laporan HR.');
        }

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
        if (!auth()->user()->canManageHrReports()) {
            abort(403, 'Anda tidak memiliki akses untuk menyimpan laporan HR.');
        }

        $validated = $request->validate([
            'report_number' => 'required|string|unique:hr_reports',
            'report_date' => 'required|date',
            'category' => 'required|string',
            'title' => 'required|string|max:255',
            'operator_name' => 'nullable|string|max:255',
            'description' => 'required|string',
            'data_link' => 'nullable|url',
            'root_cause' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'target_completion_date' => 'required|date',
            'monitoring_result' => 'required|string',
            'additional_notes' => 'nullable|string',
            'status' => 'required|in:Open,Investigating,Action Plan,Monitoring,Closed',
            'evidence_files.*' => 'nullable|file|mimes:jpg,png,pdf|max:5120',
        ]);

        // Conditional requirement for evidence_files when status is Closed
        if ($request->status === 'Closed' && !$request->hasFile('evidence_files')) {
            return back()->withErrors(['evidence_files' => 'Lampiran bukti wajib diunggah untuk menutup laporan (Status Closed).'])->withInput();
        }

        $validated['created_by'] = Auth::id();

        // Handle file uploads
        if ($request->hasFile('evidence_files')) {
            $validated['evidence_files'] = $this->handleFileUploads($request->file('evidence_files'));
        }

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
        if (!auth()->user()->canManageHrReports()) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah laporan HR.');
        }

        $report = HrReport::findOrFail($id);
        return view('hr_report.edit', compact('report'));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->canManageHrReports()) {
            abort(403, 'Anda tidak memiliki akses untuk memperbarui laporan HR.');
        }

        $report = HrReport::findOrFail($id);

        $rules = [
            'category' => 'required|string',
            'title' => 'required|string|max:255',
            'operator_name' => 'nullable|string|max:255',
            'description' => 'required|string',
            'data_link' => 'nullable|url',
            'root_cause' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'target_completion_date' => 'required|date',
            'monitoring_result' => 'required|string',
            'additional_notes' => 'nullable|string',
            'status' => 'required|in:Open,Investigating,Action Plan,Monitoring,Closed',
            'evidence_files.*' => 'nullable|file|mimes:jpg,png,pdf|max:5120',
        ];

        // Conditional requirement for evidence_files when status is Closed
        if ($request->status === 'Closed' && empty($report->evidence_files) && !$request->hasFile('evidence_files')) {
            return back()->withErrors(['evidence_files' => 'Lampiran bukti wajib diunggah untuk menutup laporan (Status Closed).'])->withInput();
        }

        $validated = $request->validate($rules);

        // Handle file uploads (append to existing)
        $files = $report->evidence_files ?? [];
        if ($request->hasFile('evidence_files')) {
            $newFiles = $this->handleFileUploads($request->file('evidence_files'));
            $files = array_merge($files, $newFiles);
        }
        $validated['evidence_files'] = $files;

        $report->update($validated);

        return redirect()->route('hr_report.show', $id)->with('success', 'Laporan HR berhasil diperbarui.');
    }

    private function handleFileUploads($uploadedFiles)
    {
        $files = [];
        foreach ($uploadedFiles as $file) {
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $destinationPath = storage_path('app/public/hr_reports/evidence');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png'])) {
                // Compress Image using GD
                $this->compressImage($file->getRealPath(), $destinationPath . '/' . $filename, $extension);
                $path = 'hr_reports/evidence/' . $filename;
            } else {
                // Just store PDF or others
                $path = $file->storeAs('hr_reports/evidence', $filename, 'public');
            }

            $files[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path
            ];
        }
        return $files;
    }

    private function compressImage($source, $destination, $extension)
    {
        $info = getimagesize($source);
        $width = $info[0];
        $height = $info[1];
        
        // Load image
        if (strtolower($extension) == 'png') {
            $image = imagecreatefrompng($source);
            imagealphablending($image, false);
            imagesavealpha($image, true);
        } else {
            $image = imagecreatefromjpeg($source);
        }

        // Resize if width > 1200px
        $max_width = 1200;
        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = floor($height * ($max_width / $width));
            $tmp_img = imagecreatetruecolor($new_width, $new_height);
            
            if (strtolower($extension) == 'png') {
                imagealphablending($tmp_img, false);
                imagesavealpha($tmp_img, true);
            }

            imagecopyresampled($tmp_img, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($image);
            $image = $tmp_img;
        }

        // Save compressed
        if (strtolower($extension) == 'png') {
            imagepng($image, $destination, 7); // PNG compression is 0-9
        } else {
            imagejpeg($image, $destination, 75); // JPEG quality 0-100
        }

        imagedestroy($image);
    }

    public function destroy($id)
    {
        if (!auth()->user()->canManageHrReports()) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus laporan HR.');
        }

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
