@extends('layouts.app')

@section('title', 'Detail Laporan HR')

@section('content')

    <div class="w-full" style="width: 100%; max-width: none;">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <a href="{{ route('hr_report.index') }}" 
                   title="Kembali ke daftar laporan"
                   class="w-10 h-10 flex items-center justify-center bg-white rounded-xl shadow-sm border border-gray-100 text-gray-400 hover:text-blue-600 transition-all">
                    <span class="material-icons-round">arrow_back</span>
                </a>

                <a href="{{ route('hr_report.pdf', $report->id) }}" target="_blank"
                    class="h-10 inline-flex items-center gap-2 px-5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all font-bold text-sm shadow-lg shadow-blue-500/20">
                    <span class="material-icons-round text-sm">picture_as_pdf</span>
                    Download PDF
                </a>

                @php
                    $isSubmittedOrApproved = in_array($report->approval_status, ['submitted', 'approved']);
                    $canEdit = auth()->user()->isHrAdmin() && !$isSubmittedOrApproved;
                    $canDelete = auth()->user()->isHrAdmin() && !$isSubmittedOrApproved && $report->status !== 'Closed';
                @endphp

                @if($canEdit)
                    <a href="{{ route('hr_report.edit', $report->id) }}"
                        class="h-10 inline-flex items-center gap-2 px-5 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 transition-all font-bold text-sm">
                        <span class="material-icons-round text-sm">edit</span>
                        Edit
                    </a>
                @endif

                @if($canDelete)
                    <form action="{{ route('hr_report.destroy', $report->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-10 h-10 flex items-center justify-center bg-red-50 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all border border-red-100">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </form>
                @endif
            </div>

            <div class="text-right">
                <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest leading-none mb-1">ID Laporan</p>
                <p class="text-xs font-mono font-bold text-gray-700">{{ $report->report_number }}</p>
            </div>
        </div>

        @php
            // Logic for status and approval styling
            $statusClasses = [
                'Open' => 'bg-red-50 text-red-600 border-red-200',
                'Investigating' => 'bg-orange-50 text-orange-600 border-orange-200',
                'Action Plan' => 'bg-blue-50 text-blue-600 border-blue-200',
                'Monitoring' => 'bg-purple-50 text-purple-600 border-purple-200',
                'Closed' => 'bg-green-50 text-green-600 border-green-200'
            ];
            $class = $statusClasses[$report->status] ?? 'bg-gray-100 text-gray-600 border-gray-200';

            $approvalClasses = [
                'draft' => 'bg-gray-50 text-gray-400 border-gray-200',
                'submitted' => 'bg-blue-50 text-blue-500 border-blue-100',
                'approved' => 'bg-green-50 text-green-700 border-green-100',
                'rejected' => 'bg-red-50 text-red-700 border-red-100'
            ];
            $approvalLabels = [
                'draft' => 'Draft',
                'submitted' => 'Waiting Approval',
                'approved' => 'Approved',
                'rejected' => 'Rejected'
            ];
            $approvalIcons = [
                'draft' => 'edit_note',
                'submitted' => 'send',
                'approved' => 'verified',
                'rejected' => 'cancel'
            ];
            $appClass = $approvalClasses[$report->approval_status] ?? 'bg-gray-100 text-gray-500 border-gray-200';
            $appLabel = $approvalLabels[$report->approval_status] ?? strtoupper($report->approval_status);
            $appIcon = $approvalIcons[$report->approval_status] ?? 'info';

            // Readiness check for Submit button
            $isComplete = $report->root_cause && 
                          $report->corrective_action && 
                          $report->target_completion_date && 
                          $report->monitoring_result && 
                          ($report->evidence_files && count($report->evidence_files) > 0);
            
            $canUpdateStatus = auth()->user()->isHrAdmin() && 
                              !in_array($report->approval_status, ['submitted', 'approved']);
        @endphp

        <!-- WORKFLOW BAR: FINAL REFINED -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row items-stretch gap-0 mb-2 min-h-[95px] overflow-hidden">
            <!-- Section 1: Progress -->
            <div class="flex-1 px-6 py-4 border-r border-gray-50 flex flex-col justify-center gap-2">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Progress</span>
                <div class="inline-flex items-center px-4 py-2 rounded-xl text-[10px] font-black border {{ $class }} uppercase tracking-widest pulse-animation shadow-sm">
                    {{ $report->status }}
                </div>
            </div>

            <!-- Section 2: Approval -->
            <div class="flex-1 px-6 py-4 border-r border-gray-50 flex flex-col justify-center gap-2">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Approval</span>
                <div class="inline-flex items-center px-4 py-2 rounded-xl text-[10px] font-black border {{ $appClass }} uppercase tracking-widest shadow-sm">
                    <span class="material-icons-round text-sm mr-2">{{ $appIcon }}</span>
                    {{ $appLabel }}
                </div>
            </div>

            <!-- Section 3: Action -->
            <div class="flex-1 px-6 py-4 border-r border-gray-50 flex flex-col justify-center gap-2">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Action</span>
                @if($canUpdateStatus)
                    <form action="{{ route('hr_report.update_status', $report->id) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="h-10 bg-gray-50 border-gray-100 rounded-xl text-[10px] font-bold uppercase tracking-widest text-gray-700 focus:ring-blue-500 focus:border-blue-400 px-3 cursor-pointer transition-all">
                            @foreach(['Open', 'Investigating', 'Action Plan', 'Monitoring'] as $st)
                                <option value="{{ $st }}" {{ $report->status == $st ? 'selected' : '' }}>{{ strtoupper($st) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/20">
                            <span class="material-icons-round text-sm">sync</span>
                            Update
                        </button>
                    </form>
                @else
                    <div class="h-10 flex items-center text-gray-400 italic text-[10px]">
                        <span class="material-icons-round text-xs mr-1">lock</span> Laporan Terkunci
                    </div>
                @endif
            </div>

            <!-- Section 4: Submit Review -->
            <div class="flex-1 px-6 py-4 flex flex-col justify-center">
                @if(auth()->user()->isHrAdmin() && in_array($report->approval_status, ['draft', 'rejected']))
                    <form action="{{ route('hr_report.submit', $report->id) }}" method="POST">
                        @csrf
                        <button type="submit" @if(!$isComplete) disabled @endif 
                            class="w-full h-11 px-6 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2 {{ $isComplete ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-500/20' : 'bg-gray-100 text-gray-300 cursor-not-allowed border border-gray-200' }}">
                            <span class="material-icons-round text-sm">{{ $isComplete ? 'send' : 'lock' }}</span>
                            Submit Review
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Helper text -->
        @if(!$isComplete && auth()->user()->isHrAdmin() && in_array($report->approval_status, ['draft', 'rejected']))
            <div class="flex items-center gap-2 text-red-500 px-4 mb-8">
                <span class="material-icons-round text-sm">warning</span>
                <span class="text-[9px] font-black uppercase tracking-widest">Complete analysis, monitoring, and evidence before submit.</span>
            </div>
        @endif

        <div class="space-y-6">
            <!-- Case Info Card -->
            <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-[0.03] scale-[4] rotate-12 select-none">
                    <span class="material-icons-round text-8xl">assignment</span>
                </div>
                
                <div class="relative z-10">
                    <div class="flex flex-wrap items-center gap-4 mb-6 pb-6 border-b border-gray-50">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 bg-blue-100 text-blue-700 rounded text-[10px] font-black uppercase tracking-wider">
                                {{ $report->category }}
                            </span>
                        </div>
                        <span class="text-gray-200">|</span>
                        <div class="flex items-center gap-2 text-gray-400">
                            <span class="material-icons-round text-sm">calendar_today</span>
                            <span class="text-xs font-bold uppercase tracking-wider">{{ \Carbon\Carbon::parse($report->report_date)->isoFormat('dddd, D MMMM Y') }}</span>
                        </div>
                        @if($report->operator_name)
                            <span class="text-gray-200">|</span>
                            <div class="flex items-center gap-2 text-gray-600">
                                <span class="material-icons-round text-sm">person</span>
                                <span class="text-xs font-black uppercase tracking-wider">{{ $report->operator_name }}</span>
                            </div>
                        @endif
                    </div>

                    <h2 class="text-3xl font-black text-gray-900 mb-6">{{ $report->title }}</h2>
                    
                    <div class="grid grid-cols-1 gap-8">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Deskripsi Masalah</label>
                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-gray-700 leading-relaxed whitespace-pre-line text-sm">
                                {{ $report->description }}
                            </div>
                        </div>

                        <div class="space-y-6">
                            @if($report->data_link)
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Tautan Data (Evidence)</label>
                                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-2xl flex items-center justify-between group">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-500/20 shrink-0">
                                                <span class="material-icons-round">link</span>
                                            </div>
                                            <div class="overflow-hidden">
                                                <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest leading-none mb-1">Traceable Link</p>
                                                <p class="text-xs font-bold text-blue-900 truncate">{{ $report->data_link }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ $report->data_link }}" target="_blank"
                                            class="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition-all shadow-md shrink-0 ml-4">
                                            Buka Bukti
                                        </a>
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

            <!-- Approval Management Panel (Manager Only) -->
            @if(auth()->user()->isHrManager() && $report->approval_status === 'submitted')
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-3xl p-8 border border-gray-700 shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 rotate-12">
                        <span class="material-icons-round text-8xl text-white">verified_user</span>
                    </div>
                    <div class="relative z-10">
                        <h3 class="text-xl font-bold text-white mb-2 flex items-center gap-2">
                            <span class="material-icons-round text-blue-400">admin_panel_settings</span>
                            Panel Approval Manager
                        </h3>
                        <p class="text-gray-400 text-sm mb-6">Tinjau laporan ini dan berikan keputusan approval.</p>
                        
                        <div class="flex flex-wrap items-center gap-4">
                            <button type="button" onclick="handleApproval('approve')" 
                                class="px-8 py-3 bg-green-500 text-white rounded-2xl font-bold hover:bg-green-600 transition-all flex items-center gap-2 shadow-lg shadow-green-500/20">
                                <span class="material-icons-round">check_circle</span>
                                Setujui Laporan
                            </button>
                            <button type="button" onclick="handleApproval('reject')" 
                                class="px-8 py-3 bg-red-500 text-white rounded-2xl font-bold hover:bg-red-600 transition-all flex items-center gap-2 shadow-lg shadow-red-500/20">
                                <span class="material-icons-round">cancel</span>
                                Tolak Laporan
                            </button>
                        </div>
                    </div>
                </div>

                <form id="approvalActionForm" method="POST" style="display: none;">
                    @csrf
                    <input type="hidden" name="note" id="approvalNote">
                </form>
            @endif

            <!-- Approval Details (If Processed) -->
            @if(in_array($report->approval_status, ['approved', 'rejected']))
                <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm flex items-start gap-6">
                    <div class="w-16 h-16 rounded-2xl {{ $report->approval_status == 'approved' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600' }} flex items-center justify-center shrink-0">
                        <span class="material-icons-round text-3xl">{{ $report->approval_status == 'approved' ? 'verified' : 'unpublished' }}</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-black text-gray-800">Informasi Approval</h3>
                                <p class="text-xs text-gray-400">Diproses oleh Manager pada {{ $report->approved_at->isoFormat('D MMMM Y, HH:mm') }}</p>
                            </div>
                            <div class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border {{ $report->approval_status == 'approved' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200' }}">
                                {{ $report->approval_status }}
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Nama Approver</label>
                                <p class="text-sm font-bold text-gray-700">{{ $report->approver->name ?? '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Catatan Manager</label>
                                <p class="text-sm text-gray-600 italic">{{ $report->approval_note ?: 'Tidak ada catatan.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

    <script>
        function handleStatusChange(select) {
            const newStatus = select.value;
            const originalStatus = "{{ $report->status }}";

            if (newStatus === 'Closed') {
                Swal.fire({
                    title: 'Closed laporan ini?',
                    text: "Laporan yang sudah close tidak bisa diedit kembali!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, lanjutkan',
                    cancelButtonText: 'Tidak',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        select.form.submit();
                    } else {
                        // Reset select to original status
                        select.value = originalStatus;
                    }
                });
            } else {
                select.form.submit();
            }
        }

        function handleApproval(action) {
            const isReject = action === 'reject';
            
            Swal.fire({
                title: isReject ? 'Tolak Laporan' : 'Setujui Laporan',
                text: isReject ? 'Berikan alasan penolakan agar Admin dapat memperbaiki data:' : 'Berikan catatan (opsional) untuk persetujuan ini:',
                input: 'textarea',
                inputPlaceholder: 'Tuliskan catatan di sini...',
                showCancelButton: true,
                confirmButtonColor: isReject ? '#ef4444' : '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: isReject ? 'Tolak Sekarang' : 'Ya, Setujui',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                inputValidator: (value) => {
                    if (isReject && (!value || value.length < 5)) {
                        return 'Alasan penolakan wajib diisi (minimal 5 karakter)!'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('approvalActionForm');
                    const url = action === 'approve' 
                        ? "{{ route('hr_report.approve', $report->id) }}" 
                        : "{{ route('hr_report.reject', $report->id) }}";
                    
                    form.action = url;
                    document.getElementById('approvalNote').value = result.value;
                    form.submit();
                }
            });
        }
    </script>

    <style>
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.1); }
            70% { box-shadow: 0 0 0 10px rgba(0, 0, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 0, 0, 0); }
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
    </style>        <!-- Analysis & Action Card -->
            <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-8 border-b border-gray-50 pb-6">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                        <span class="material-icons-round">analytics</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-800">Analisis & Tindakan</h3>
                        <p class="text-xs text-gray-400">Hasil investigasi dan rencana perbaikan</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-orange-400 rounded-full"></span>
                            <label class="text-[10px] font-bold text-orange-400 uppercase tracking-widest">Akar Masalah (Root Cause)</label>
                        </div>
                        <div class="bg-orange-50/20 rounded-2xl p-6 border border-orange-100/50 text-gray-700 text-sm leading-relaxed min-h-[100px]">
                            @if($report->root_cause)
                                {{ $report->root_cause }}
                            @else
                                <span class="text-gray-400 italic">Belum ada analisis akar masalah.</span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <label class="text-[10px] font-bold text-green-500 uppercase tracking-widest">Tindakan Perbaikan</label>
                        </div>
                        <div class="bg-green-50/20 rounded-2xl p-6 border border-green-100/50 text-gray-700 text-sm leading-relaxed min-h-[100px]">
                            @if($report->corrective_action)
                                {{ $report->corrective_action }}
                            @else
                                <span class="text-gray-400 italic">Belum ada rencana tindakan perbaikan.</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-50">
                    <div class="flex items-center gap-2">
                        <span class="material-icons-round text-blue-500 text-sm">event_available</span>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Target Penyelesaian</label>
                    </div>
                    <p class="mt-1 text-sm font-black text-blue-700 bg-blue-50 inline-block px-3 py-1 rounded-lg">
                        {{ $report->target_completion_date ? $report->target_completion_date->isoFormat('dddd, D MMMM Y') : '-' }}
                    </p>
                </div>
            </div>

            <!-- Monitoring & Evidence Card -->
            <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-8 border-b border-gray-50 pb-6">
                    <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center">
                        <span class="material-icons-round">visibility</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-800">Monitoring & Hasil</h3>
                        <p class="text-xs text-gray-400">Pantauan lapangan dan bukti pendukung</p>
                    </div>
                </div>

                <div class="space-y-8">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Hasil Monitoring</label>
                        <div class="bg-purple-50/20 rounded-2xl p-6 border border-purple-100/50 text-gray-700 text-sm leading-relaxed whitespace-pre-line min-h-[100px]">
                            @if($report->monitoring_result)
                                {{ $report->monitoring_result }}
                            @else
                                <span class="text-gray-400 italic">Belum ada hasil monitoring yang dicatat.</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Lampiran Bukti</label>
                        @if($report->evidence_files && count($report->evidence_files) > 0)
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                @foreach($report->evidence_files as $file)
                                    @php
                                        $extension = pathinfo($file['path'], PATHINFO_EXTENSION);
                                        $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png']);
                                    @endphp
                                    
                                    <div class="group relative bg-gray-50 rounded-2xl border border-gray-100 overflow-hidden aspect-square flex flex-col items-center justify-center transition-all hover:shadow-lg hover:border-blue-200">
                                        @if($isImage)
                                            <img src="{{ asset('storage/' . $file['path']) }}" class="w-full h-full object-cover transition-transform group-hover:scale-110">
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                                <a href="{{ asset('storage/' . $file['path']) }}" target="_blank" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-blue-600 shadow-xl">
                                                    <span class="material-icons-round">zoom_in</span>
                                                </a>
                                            </div>
                                        @else
                                            <span class="material-icons-round text-red-400 text-4xl mb-2">picture_as_pdf</span>
                                            <span class="text-[9px] font-bold text-gray-500 px-2 text-center truncate w-full">{{ $file['name'] }}</span>
                                            <a href="{{ asset('storage/' . $file['path']) }}" target="_blank" class="absolute inset-0"></a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 border-dashed text-center">
                                <p class="text-xs text-gray-400 italic">Tidak ada lampiran bukti.</p>
                            </div>
                        @endif
                    </div>

                    @if($report->additional_notes)
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Catatan Tambahan</label>
                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-gray-700 text-sm leading-relaxed whitespace-pre-line">
                                {{ $report->additional_notes }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>


            
            <div class="text-center py-8">
                <p class="text-[10px] text-gray-300 font-mono tracking-widest uppercase">KPI-Bubut Internal Tracking System • {{ date('Y') }}</p>
            </div>
        </div>
    </div>

    <style>
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.1); }
            70% { box-shadow: 0 0 0 10px rgba(0, 0, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 0, 0, 0); }
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
    </style>

@endsection
