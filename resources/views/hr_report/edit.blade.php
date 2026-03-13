@extends('layouts.app')

@section('title', 'Edit Laporan HR')

@section('content')

    <div class="max-w-5xl mx-auto pb-6">
        <!-- Header: Consistent with Create -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('hr_report.show', $report->id) }}" 
                   class="w-8 h-8 flex items-center justify-center bg-white rounded-lg shadow-sm border border-gray-100 text-gray-400 hover:text-blue-600 transition-all">
                    <span class="material-icons-round text-sm">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-xl font-black text-gray-800 tracking-tight leading-none">Edit Laporan HR</h1>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">Perbarui Informasi Issue <b>{{ $report->report_number }}</b></p>
                </div>
            </div>
        </div>

        <form action="{{ route('hr_report.update', $report->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="space-y-3">
                
                <!-- CARD 1: METADATA (Consistent & Compact) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-icons-round text-blue-500 text-lg">info</span>
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Informasi Laporan</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">ID Laporan</label>
                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-2.5">
                                    <p class="text-[11px] font-mono font-bold text-blue-700">{{ $report->report_number }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Tanggal Terbit</label>
                                <div class="bg-gray-50 border border-gray-100 rounded-xl p-2.5 flex items-center gap-2">
                                    <span class="material-icons-round text-gray-400 text-sm">calendar_today</span>
                                    <p class="text-[11px] font-bold text-gray-700">{{ \Carbon\Carbon::parse($report->report_date)->isoFormat('dddd, D MMM YYYY') }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5 text-blue-500">Update Status</label>
                                <select name="status" required
                                    class="w-full bg-blue-50 border-blue-100 rounded-xl text-[11px] font-black text-blue-600 focus:ring-0 h-10 px-3 cursor-pointer">
                                    @foreach(['Open' => '🔴 OPEN', 'Investigating' => '🟠 INVESTIGATING', 'Action Plan' => '🔵 ACTION PLAN', 'Monitoring' => '🟣 MONITORING', 'Closed' => '🟢 CLOSED'] as $val => $label)
                                        <option value="{{ $val }}" {{ $report->status == $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: ISSUE DETAILS -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-icons-round text-orange-400 text-lg">report_problem</span>
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Detail Masalah</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-1">
                                <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Kategori Masalah <span class="text-red-500">*</span></label>
                                <select name="category" required
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all h-10 px-3">
                                    @foreach(['Cycle Time', 'KPI Operator', 'Downtime Mesin', 'Quality / Scrap', 'Disiplin Operator', 'Maintenance', 'Lainnya'] as $cat)
                                        <option value="{{ $cat }}" {{ $report->category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Judul Laporan <span class="text-red-500">*</span></label>
                                <input type="text" name="title" value="{{ $report->title }}" required placeholder="Misal: Penurunan KPI Signifikan Shift 1"
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all h-10 px-3">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Deskripsi Masalah <span class="text-red-500">*</span></label>
                            <textarea name="description" required rows="3" placeholder="Jelaskan detail anomali yang Anda temukan..."
                                class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all p-3">{{ $report->description }}</textarea>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Link Bukti Data (Traceable URL)</label>
                            <div class="relative">
                                <input type="url" name="data_link" value="{{ $report->data_link }}" placeholder="http://10.88.8.97/kpi-bubut/public/daily-report/operator/show/..."
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 pl-10 focus:border-blue-400 transition-all h-10 px-3">
                                <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">link</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: ANALYSIS & ACTION -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-icons-round text-green-500 text-lg">psychology</span>
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Analisis & Tindakan</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 mb-1.5 uppercase tracking-tighter">Penyebab Masalah (Root Cause)</label>
                                <textarea name="root_cause" rows="2" placeholder="Faktor penyebab utama..."
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-orange-400 transition-all p-3">{{ $report->root_cause }}</textarea>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-green-600 mb-1.5 uppercase tracking-tighter">Tindakan Perbaikan (Corrective Action)</label>
                                <textarea name="corrective_action" rows="2" placeholder="Langkah perbaikan..."
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-green-400 transition-all p-3">{{ $report->corrective_action }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="flex items-center justify-between pt-4">
                    <div class="text-[9px] text-gray-400 italic">
                        Terakhir diupdate: {{ $report->updated_at->diffForHumans() }}
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('hr_report.show', $report->id) }}" 
                           class="px-6 py-2 rounded-xl text-xs font-bold text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 transition-all">
                            Batal
                        </a>
                        <button type="submit" 
                                class="px-14 py-4 bg-blue-600 text-white rounded-2xl text-sm font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-3">
                             <span class="material-icons-round text-lg">save</span>
                             Perbarui Laporan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@push('styles')
<style>
    /* Compact scrollbar */
    textarea {
        scrollbar-width: thin;
        scrollbar-color: rgba(0,0,0,0.1) transparent;
    }
    textarea::-webkit-scrollbar {
        width: 4px;
    }
    textarea::-webkit-scrollbar-thumb {
        background-color: rgba(0,0,0,0.1);
        border-radius: 10px;
    }
</style>
@endpush
