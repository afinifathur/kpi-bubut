@extends('layouts.app')

@section('title', 'Laporan HR Baru')

@section('content')

    <div class="max-w-5xl mx-auto pb-6">
        <!-- Header: More Compact -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('hr_report.index') }}" 
                   class="w-8 h-8 flex items-center justify-center bg-white rounded-lg shadow-sm border border-gray-100 text-gray-400 hover:text-blue-600 transition-all">
                    <span class="material-icons-round text-sm">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-xl font-black text-gray-800 tracking-tight leading-none">Buat Laporan HR Baru</h1>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">Dokumentasi Issue & Anomali Sistem</p>
                </div>
            </div>
        </div>

        <form action="{{ route('hr_report.store') }}" method="POST">
            @csrf
            
            <div class="space-y-3">
                
                <!-- CARD 1: METADATA (Very Compact) -->
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
                                    <p class="text-[11px] font-mono font-bold text-blue-700">{{ $reportNumber }}</p>
                                    <input type="hidden" name="report_number" value="{{ $reportNumber }}">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Tanggal Terbit</label>
                                <div class="bg-gray-50 border border-gray-100 rounded-xl p-2.5 flex items-center gap-2">
                                    <span class="material-icons-round text-gray-400 text-sm">calendar_today</span>
                                    <p class="text-[11px] font-bold text-gray-700">{{ \Carbon\Carbon::parse($today)->isoFormat('dddd, D MMM YYYY') }}</p>
                                    <input type="hidden" name="report_date" value="{{ $today }}">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5 text-red-500">Status Awal</label>
                                <select name="status" required
                                    class="w-full bg-red-50 border-red-100 rounded-xl text-[11px] font-black text-red-600 focus:ring-0 h-10 px-3 cursor-pointer">
                                    <option value="Open">🔴 OPEN</option>
                                    <option value="Investigating">🟠 INVESTIGATING</option>
                                    <option value="Action Plan">🔵 ACTION PLAN</option>
                                    <option value="Monitoring">🟣 MONITORING</option>
                                    <option value="Closed">🟢 CLOSED</option>
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
                                    <option value="" disabled selected>Pilih Kategori</option>
                                    <option value="Cycle Time">Cycle Time</option>
                                    <option value="KPI Operator">KPI Operator</option>
                                    <option value="Downtime Mesin">Downtime Mesin</option>
                                    <option value="Quality / Scrap">Quality / Scrap</option>
                                    <option value="Disiplin Operator">Disiplin Operator</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Judul Laporan <span class="text-red-500">*</span></label>
                                <input type="text" name="title" required placeholder="Misal: Penurunan KPI Signifikan Shift 1"
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all h-10 px-3">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Deskripsi Masalah <span class="text-red-500">*</span></label>
                            <textarea name="description" required rows="3" placeholder="Jelaskan detail anomali yang Anda temukan..."
                                class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all p-3"></textarea>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Link Bukti Data (Traceable URL)</label>
                            <div class="relative">
                                <input type="url" name="data_link" placeholder="http://10.88.8.97/kpi-bubut/public/daily-report/operator/show/..."
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 pl-10 focus:border-blue-400 transition-all h-10 px-3">
                                <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">link</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: ANALYSIS & ACTION (Most Compact) -->
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
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-orange-400 transition-all p-3"></textarea>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-green-600 mb-1.5 uppercase tracking-tighter">Tindakan Perbaikan (Corrective Action)</label>
                                <textarea name="corrective_action" rows="2" placeholder="Langkah perbaikan..."
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-green-400 transition-all p-3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS: Fixed at bottom or tight -->
                <div class="flex items-center justify-between pt-4">
                    <button type="reset" 
                            class="px-5 py-2 rounded-xl text-xs font-bold text-gray-400 hover:text-gray-600 transition-all">
                        Reset Form
                    </button>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('hr_report.index') }}" 
                           class="px-6 py-2 rounded-xl text-xs font-bold text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 transition-all">
                            Batal
                        </a>
                        <button type="submit" 
                                class="px-14 py-4 bg-blue-600 text-white rounded-2xl text-sm font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-3">
                             <span class="material-icons-round text-lg">save</span>
                             Simpan Laporan Baru
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@push('styles')
<style>
    /* Compact scrollbar for textareas */
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
